"""Task 工具：派生子 agent 执行独立任务。"""

import threading
import time
from typing import Any, Dict, List

from tools.base import BaseTool, ToolContext, ToolResult


class TaskTool(BaseTool):
    name = "Task"
    description = "派生一个独立子 agent 执行特定任务。子 agent 有自己的上下文（不共享主对话历史），可指定不同 system prompt 和工具子集。适用于：代码审查、独立调研、并行子任务等。返回子 agent 的最终结果。"
    parameters = {
        "type": "object",
        "properties": {
            "description": {"type": "string", "description": "任务简短描述（3-5 词）"},
            "prompt": {"type": "string", "description": "给子 agent 的完整任务指令，应自包含所有上下文"},
            "subagent_type": {"type": "string", "description": "子 agent 类型，决定 system prompt 和工具集：'general'（默认，全工具）/ 'reviewer'（只读，代码审查）/ 'explorer'（只读，调研）"},
        },
        "required": ["description", "prompt"],
    }
    risk_level = "read_only"

    # 不同类型的工具白名单
    SUBAGENT_TOOLS = {
        "general": ["Read", "Write", "Edit", "Grep", "Glob", "Bash"],
        "reviewer": ["Read", "Grep", "Glob"],
        "explorer": ["Read", "Grep", "Glob", "Bash"],
    }

    SUBAGENT_PROMPTS = {
        "general": "你是一个通用编程助手，可以读写文件、执行命令、修改代码。",
        "reviewer": "你是代码审查专家。只读取和分析代码，不做修改。重点关注：bug、安全、性能、可维护性。给出具体可执行的改进建议。",
        "explorer": "你是代码库调研助手。通过读取文件、搜索内容、跑只读命令来回答关于代码库的问题。不要修改任何文件。",
    }

    # 子 agent 最大执行时间（秒）和最大轮数
    SUBAGENT_TIMEOUT = 120
    SUBAGENT_MAX_ROUNDS = 15

    def execute(self, params: Dict[str, Any], context: ToolContext) -> ToolResult:
        description = params.get("description", "")
        prompt = params.get("prompt", "")
        subagent_type = params.get("subagent_type", "general")

        if not prompt:
            return ToolResult(success=False, error="prompt 参数必填")

        if subagent_type not in self.SUBAGENT_TOOLS:
            return ToolResult(
                success=False,
                error=f"未知 subagent_type: {subagent_type}，可选: {list(self.SUBAGENT_TOOLS.keys())}",
            )

        # 延迟导入避免循环依赖
        from core.agent_loop import AgentLoop

        tool_names = self.SUBAGENT_TOOLS[subagent_type]
        system_prompt = self.SUBAGENT_PROMPTS[subagent_type]

        # 子 agent 共享主 agent 的工具实例（用 tools_filter 限制白名单）
        tools_dict = getattr(context, "tools", None) or {}
        if not tools_dict:
            return ToolResult(success=False, error="无法获取工具集（context.tools 未设置）")

        # 创建子 agent 循环（限制 max_rounds）
        sub_loop = AgentLoop(
            provider=context.provider,
            permission_engine=context.permission_engine,
            skill_loader=None,
            tools=tools_dict,
            tools_filter=tool_names,
            work_dir=context.work_dir,
            max_rounds=self.SUBAGENT_MAX_ROUNDS,
            is_subagent=True,
            cli=None,
        )

        # 在子线程执行，主线程等待有超时
        result_holder: Dict[str, Any] = {"text": "", "error": None}
        done_event = threading.Event()

        def run_subagent():
            try:
                result_holder["text"] = sub_loop.run_subagent(prompt, system_prompt)
            except Exception as e:
                result_holder["error"] = e
            finally:
                done_event.set()

        t = threading.Thread(target=run_subagent, daemon=True)
        t.start()

        # 等待完成或超时
        finished = done_event.wait(timeout=self.SUBAGENT_TIMEOUT)
        if not finished:
            return ToolResult(
                success=False,
                error=f"子 agent 执行超时（{self.SUBAGENT_TIMEOUT}s 已强制终止）",
            )
        if result_holder["error"]:
            return ToolResult(success=False, error=f"子 agent 执行失败: {result_holder['error']}")

        return ToolResult(success=True, output=result_holder["text"] or "[子 agent 未返回内容]")
