"""Agent 主循环。

串联 Provider / Tools / Permissions / Skills / ContextManager / UI。
"""

import time
from typing import Any, Dict, List, Optional

from rich.console import Console, Group
from rich.live import Live
from rich.markdown import Markdown
from rich.text import Text

from core.message import Message, ToolCall
from core.context_manager import ContextManager
from permissions.engine import PermissionEngine
from providers.base import BaseProvider, ProviderEvent
from skills.loader import SkillLoader
from tools.base import BaseTool, ToolContext, ToolResult
from ui.cli import CLI
from ui.thinking_status import ThinkingStatus, estimate_tokens
from ui.tool_display import display_tool_start, display_tool_result, execute_with_spinner


console = Console()


def detect_repetition(text: str, min_phrase_len: int = 5, min_repeats: int = 3) -> tuple:
    """检测文本末尾是否陷入重复循环。

    检查 text 末尾是否有某个长度 >= min_phrase_len 的短语连续重复 >= min_repeats 次。
    返回 (是否重复, 截断索引)。
    截断索引指向非重复部分的末尾，text[:截断索引] 是应保留的内容。
    """
    if len(text) < min_phrase_len * min_repeats:
        return False, len(text)

    # 只检查最后 800 字符，避免大文本导致计算量大
    tail = text[-800:]
    tail_len = len(tail)

    # 从短到长尝试不同短语长度
    max_phrase_len = min(80, tail_len // min_repeats)
    for phrase_len in range(min_phrase_len, max_phrase_len + 1):
        phrase = tail[-phrase_len:]
        repeats = 1
        pos = tail_len - phrase_len
        while pos - phrase_len >= 0 and tail[pos - phrase_len:pos] == phrase:
            repeats += 1
            pos -= phrase_len
            if repeats >= min_repeats:
                # 找到重复，pos 指向第一次重复的起点
                cut_in_tail = pos
                cut_in_text = len(text) - tail_len + cut_in_tail
                return True, cut_in_text
    return False, len(text)


class AgentLoop:
    def __init__(
        self,
        provider: BaseProvider,
        permission_engine: PermissionEngine,
        skill_loader: Optional[SkillLoader],
        tools: Dict[str, BaseTool],
        work_dir: str,
        max_rounds: int = 30,
        cli: Optional[CLI] = None,
        is_subagent: bool = False,
        tools_filter: Optional[List[str]] = None,
    ):
        self.provider = provider
        self.permission_engine = permission_engine
        self.skill_loader = skill_loader
        self.tools = tools
        self.work_dir = work_dir
        self.max_rounds = max_rounds
        self.cli = cli
        self.is_subagent = is_subagent
        self.tools_filter = tools_filter

        self.messages: List[Message] = []
        self.system_prompt: str = ""
        self.context_manager = ContextManager(provider=provider)

    def set_system_prompt(self, prompt: str):
        self.system_prompt = prompt

    def load_history(self, messages: List[Message]):
        self.messages = messages

    def get_history(self) -> List[Message]:
        return self.messages

    def clear_memory(self):
        self.messages.clear()

    def run(self, user_input: str) -> str:
        """主循环：处理一次用户输入，返回最终文本回复。"""
        # 1. 检查技能触发
        skill_prompt = self._check_skill_trigger(user_input)
        effective_system = self.system_prompt + ("\n\n" + skill_prompt if skill_prompt else "")

        # 2. 加入用户消息
        self.messages.append(Message.user(user_input))

        # 3. 上下文压缩
        if self.context_manager.should_compress(self.messages):
            if not self.is_subagent:
                self.cli.info("对话历史较长，正在压缩...")
            self.messages = self.context_manager.compress(self.messages)

        # 4. 构造工具定义
        tools_schema = self._build_tools_schema()

        # 5. 主循环
        final_text = ""
        for round_idx in range(self.max_rounds):
            try:
                round_text, tool_calls = self._call_model(effective_system, tools_schema)
            except KeyboardInterrupt:
                if not self.is_subagent:
                    self.cli.warn("已中断当前操作")
                break

            final_text = round_text
            self.messages.append(Message.assistant(content=round_text or None, tool_calls=tool_calls or None))

            if not tool_calls:
                break

            for tc in tool_calls:
                result = self._execute_tool_call(tc)
                self.messages.append(Message.tool(tool_call_id=tc.id, name=tc.name, content=result.to_str()))

        return final_text

    def run_subagent(self, prompt: str, system_prompt: str) -> str:
        """子 agent 运行入口：用独立的 system prompt 和工具集跑一轮。"""
        self.system_prompt = system_prompt
        return self.run(prompt)

    def _check_skill_trigger(self, user_input: str) -> Optional[str]:
        if not self.skill_loader:
            return None
        # 手动触发
        skill = self.skill_loader.match_trigger(user_input)
        if skill:
            content_after_cmd = user_input.strip()
            content_after_cmd = content_after_cmd[len("/" + skill.name):].strip()
            return f"[激活技能: {skill.name}]\n用户输入: {content_after_cmd}\n\n{skill.content}"
        # 自动匹配提示
        return self.skill_loader.build_skills_hint() or None

    def _build_tools_schema(self) -> List[Dict[str, Any]]:
        schema = []
        for name, tool in self.tools.items():
            if self.tools_filter and name not in self.tools_filter:
                continue
            schema.append(tool.to_openai_schema())
        return schema

    def _call_model(
        self,
        system_prompt: str,
        tools_schema: List[Dict[str, Any]],
    ) -> tuple[str, List[ToolCall]]:
        """调用模型，流式渲染文本，收集工具调用。

        每轮独立管理渲染状态：
        - 思考阶段：ThinkingStatus 动态显示（✽ Wibbling… (5s)）
        - 流式文本：Live 纯文本流式显示
        - 流式结束：停 Live，用 Markdown 重新渲染本轮文本
        """
        tool_calls: List[ToolCall] = []
        repetition_detected = False
        round_text_parts: List[str] = []
        live: Optional[Live] = None
        thinking: Optional[ThinkingStatus] = None

        if not self.is_subagent:
            thinking = ThinkingStatus(console)
            thinking.start()

        try:
            events = self.provider.chat(
                messages=self.messages,
                tools=tools_schema,
                system_prompt=system_prompt,
            )

            for event in events:
                if event.type == "text_delta":
                    # 第一个文本片段：停思考状态，启动 Live 流式渲染
                    if live is None and not self.is_subagent:
                        if thinking:
                            thinking.stop()
                            thinking = None
                        console.print()
                        console.print("[bold magenta]🤖 助手:[/bold magenta]")
                        live = Live(
                            Group(Text(""), Text("")),
                            console=console,
                            refresh_per_second=15,
                            vertical_overflow="visible",
                            transient=True,
                        )
                        live.start()
                    round_text_parts.append(event.text or "")
                    full_text = "".join(round_text_parts)
                    if live:
                        tokens = estimate_tokens(full_text)
                        live.update(Group(
                            Text(full_text),
                            Text(f"  ↓ {tokens} tokens", style="dim cyan"),
                        ))
                    # 重复检测：每 20 个片段检测一次，避免频繁检测影响性能
                    # 只在文本较长时检测（> 200 字符）
                    if len(round_text_parts) % 20 == 0 and len(full_text) > 200:
                        is_rep, cut_idx = detect_repetition(full_text)
                        if is_rep:
                            repetition_detected = True
                            round_text_parts = [full_text[:cut_idx]]
                            if live:
                                live.update(Group(
                                    Text(full_text[:cut_idx]),
                                    Text(f"  ↓ {estimate_tokens(full_text[:cut_idx])} tokens", style="dim cyan"),
                                ))
                            break
                elif event.type == "tool_call":
                    # 工具调用前停 live，用 Markdown 重新渲染本轮文本
                    if live:
                        live.stop()
                        live = None
                        self._render_markdown("".join(round_text_parts))
                    if thinking:
                        thinking.stop()
                        thinking = None
                    if event.tool_call:
                        tool_calls.append(event.tool_call)
                elif event.type == "error":
                    if live:
                        live.stop()
                        live = None
                    if thinking:
                        thinking.stop()
                        thinking = None
                    if not self.is_subagent:
                        self.cli.error(event.error or "未知错误")
                    else:
                        raise RuntimeError(event.error or "Provider 错误")
                elif event.type == "done":
                    break

            # 流式正常结束（无 tool_call）：停 Live，用 Markdown 重新渲染
            if live:
                live.stop()
                live = None
                self._render_markdown("".join(round_text_parts))
            if thinking:
                thinking.stop()
                thinking = None
        finally:
            if live:
                live.stop()
            if thinking:
                thinking.stop()

        # 重复检测后给用户提示
        if repetition_detected and not self.is_subagent:
            console.print()
            self.cli.warn("检测到模型输出陷入重复，已自动截断")

        return "".join(round_text_parts), tool_calls

    def _render_markdown(self, text: str):
        """用 Markdown 重新渲染本轮文本（表格、加粗、代码块等）。"""
        if not text.strip():
            return
        try:
            console.print(Markdown(text))
        except Exception:
            console.print(text)

    def _execute_tool_call(self, tc: ToolCall) -> ToolResult:
        """执行一次工具调用，含权限检查。"""
        tool = self.tools.get(tc.name)
        if not tool:
            return ToolResult(success=False, error=f"未知工具: {tc.name}")

        # 子 agent 工具白名单检查
        if self.tools_filter and tc.name not in self.tools_filter:
            return ToolResult(success=False, error=f"子 agent 无权使用工具: {tc.name}")

        # 权限检查
        decision = self.permission_engine.check(tc.name, tc.arguments, tool.risk_level)

        if decision.decision == "deny":
            if not self.is_subagent:
                self.cli.warn(f"权限拒绝: {decision.reason}")
            return ToolResult(success=False, error=f"权限拒绝: {decision.reason}")

        if decision.decision == "ask" and not self.is_subagent:
            choice, feedback = self.cli.ask_permission(tc.name, tc.arguments, tool.risk_level, decision.reason)
            if choice == "n":
                return ToolResult(success=False, error="用户拒绝")
            if choice == "e":
                return ToolResult(success=False, error=f"用户拒绝并给出反馈: {feedback or '(空)'}")
            if choice == "Y":
                self.permission_engine.add_allow(tc.name, tc.arguments)

        # 展示工具调用开始
        if not self.is_subagent:
            start_time = display_tool_start(tc.name, tc.arguments, tool.risk_level)
        else:
            start_time = time.time()

        # 构造上下文
        ctx = ToolContext(
            work_dir=self.work_dir,
            session_id=str(id(self)),
            permission_engine=self.permission_engine,
            provider=self.provider,
            tools=self.tools,
            skill_loader=self.skill_loader,
        )

        # 执行（带 spinner 指示）
        try:
            result = execute_with_spinner(tc.name, tool, tc.arguments, ctx, self.is_subagent)
        except Exception as e:
            result = ToolResult(success=False, error=f"工具执行异常: {e}")

        # 展示结果
        if not self.is_subagent:
            display_tool_result(result.success, result.output, result.error, start_time)

        return result
