"""会话历史管理。

负责会话的列出、加载、保存、预览等操作。
从 agent_main.py 提取，供 CLI 和主循环共用。
"""

import datetime
import json
import os
from typing import List, Optional, Tuple

from rich.console import Console

from core.message import Message, ToolCall

console = Console()


def generate_session_id() -> str:
    """生成时间戳会话 ID"""
    return datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")


def get_history_dir(base_dir: str) -> str:
    """获取会话历史目录路径"""
    return os.path.join(base_dir, "config", "chat_history")


def list_sessions(base_dir: str, limit: int = 20) -> List[str]:
    """列出最近的会话文件名，按修改时间倒序"""
    history_dir = get_history_dir(base_dir)
    if not os.path.exists(history_dir):
        return []
    files = [f for f in os.listdir(history_dir) if f.endswith(".json")]
    files.sort(
        key=lambda f: os.path.getmtime(os.path.join(history_dir, f)),
        reverse=True,
    )
    return files[:limit]


def load_session(base_dir: str, session_id_or_file: str) -> List[Message]:
    """加载指定会话。参数可以是 session_id 或完整文件名"""
    history_dir = get_history_dir(base_dir)
    if not session_id_or_file.endswith(".json"):
        session_id_or_file = session_id_or_file + ".json"
    file_path = os.path.join(history_dir, session_id_or_file)
    if not os.path.exists(file_path):
        return []
    try:
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        return _deserialize_messages(data)
    except Exception as e:
        console.print(f"[yellow]⚠️ 加载会话失败: {e}[/yellow]")
        return []


def _deserialize_messages(data: list) -> List[Message]:
    """把 dict 列表反序列化为 Message 列表。跳过 system 消息（避免协议污染）"""
    msgs = []
    skipped_system = 0
    for m in data:
        if m.get("role") == "system":
            skipped_system += 1
            continue
        tool_calls = None
        if m.get("tool_calls"):
            tool_calls = [ToolCall(id=tc["id"], name=tc["name"], arguments=tc["arguments"]) for tc in m["tool_calls"]]
        msgs.append(Message(
            role=m["role"],
            content=m.get("content"),
            tool_calls=tool_calls,
            tool_call_id=m.get("tool_call_id"),
            name=m.get("name"),
        ))
    if skipped_system:
        console.print(f"[yellow]⚠️ 已跳过 {skipped_system} 条旧版 system 消息（避免协议污染）[/yellow]")
    return msgs


def save_session(base_dir: str, messages: List[Message], session_id: str) -> str:
    """保存会话到 chat_history/{session_id}.json。返回文件路径"""
    history_dir = get_history_dir(base_dir)
    os.makedirs(history_dir, exist_ok=True)
    file_path = os.path.join(history_dir, f"{session_id}.json")
    try:
        data = []
        for m in messages:
            d = {"role": m.role}
            if m.content is not None:
                d["content"] = m.content
            if m.tool_calls:
                d["tool_calls"] = [
                    {"id": tc.id, "name": tc.name, "arguments": tc.arguments}
                    for tc in m.tool_calls
                ]
            if m.tool_call_id is not None:
                d["tool_call_id"] = m.tool_call_id
            if m.name is not None:
                d["name"] = m.name
            data.append(d)
        with open(file_path, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        return file_path
    except Exception as e:
        console.print(f"[yellow]⚠️ 保存会话失败: {e}[/yellow]")
        return ""


def get_session_preview(base_dir: str, session_file: str, max_len: int = 60) -> str:
    """获取会话预览（第一条 user 消息）"""
    msgs = load_session(base_dir, session_file)
    for m in msgs:
        if m.role == "user" and m.content:
            preview = m.content.strip().replace("\n", " ")
            return preview[:max_len] + ("..." if len(preview) > max_len else "")
    return "(空会话)"


def get_session_msg_count(base_dir: str, session_file: str) -> int:
    """获取会话消息条数（不反序列化，直接读 JSON 数组长度）"""
    history_dir = get_history_dir(base_dir)
    file_path = os.path.join(history_dir, session_file)
    try:
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        return len(data)
    except Exception:
        return 0


def pick_session_interactive(base_dir: str, limit: int = 15) -> Optional[Tuple[str, List[Message]]]:
    """交互式选择要恢复的会话。

    返回 (session_id, messages)，取消返回 None。
    直接在 CLI 层面操作，不需要访问大模型。
    """
    sessions = list_sessions(base_dir, limit)
    if not sessions:
        console.print("\n  [dim]暂无历史会话[/dim]\n")
        return None

    console.print()
    console.print("  [bold]可用历史会话（按时间倒序）:[/bold]")
    for i, f in enumerate(sessions):
        sid = f[:-5]  # 去掉 .json
        preview = get_session_preview(base_dir, f)
        msg_count = get_session_msg_count(base_dir, f)
        console.print(f"    [yellow][{i}][/yellow] {sid}  [dim]({msg_count}条) | {preview}[/dim]")
    console.print()

    try:
        choice = input("  选择会话编号（回车取消）: ").strip()
    except (EOFError, KeyboardInterrupt):
        console.print()
        return None

    if not choice:
        return None

    try:
        idx = int(choice)
        if 0 <= idx < len(sessions):
            session_id = sessions[idx][:-5]
            history = load_session(base_dir, sessions[idx])
            if history:
                return session_id, history
            else:
                console.print("  [red]❌ 该会话为空或加载失败[/red]")
                return None
    except (ValueError, IndexError):
        pass

    console.print("  [yellow]⚠️ 无效选择[/yellow]")
    return None
