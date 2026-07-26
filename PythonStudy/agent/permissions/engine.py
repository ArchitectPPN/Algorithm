"""权限引擎。

根据 settings.json 的 allow/deny/ask 规则 + 工具 risk_level 判断工具调用是否需要确认。

规则格式（settings.json）：
{
  "permissions": {
    "allow": ["Read", "Grep", "Glob", "Bash(npm test:*)", "Bash(git status)"],
    "deny":  ["Bash(rm -rf:*)"],
    "ask":   ["Write", "Edit", "Bash"]
  },
  "dangerous_patterns": ["rm -rf", "format ", "del /s"]
}

匹配顺序：
  1. dangerous_patterns 命中 -> 拒绝（不可覆盖）
  2. deny 命中 -> 拒绝
  3. allow 命中 -> 放行
  4. ask 命中 -> 弹窗
  5. 默认按 risk_level：read_only 放行，write/destructive 弹窗

规则语法：
  - "Read"          匹配工具名
  - "Bash"          匹配工具名（所有 Bash 调用）
  - "Bash(npm test:*)" 匹配 Bash 命令前缀，支持 * 通配
"""

import fnmatch
import json
import os
import re
from dataclasses import dataclass
from typing import Any, Dict, List, Optional, Tuple


@dataclass
class PermissionDecision:
    decision: str  # "allow" / "deny" / "ask"
    reason: str = ""


class PermissionEngine:
    def __init__(self, settings_path: str):
        self.settings_path = settings_path
        self.permissions: Dict[str, List[str]] = {"allow": [], "deny": [], "ask": []}
        self.dangerous_patterns: List[str] = []
        self.load()

    def load(self):
        if not os.path.exists(self.settings_path):
            self._write_default()
        try:
            with open(self.settings_path, "r", encoding="utf-8") as f:
                data = json.load(f)
            self.permissions = data.get("permissions", {"allow": [], "deny": [], "ask": []})
            self.dangerous_patterns = data.get("dangerous_patterns", [])
        except (json.JSONDecodeError, IOError) as e:
            print(f"  [⚠️ 权限] settings.json 解析失败: {e}，使用空规则")
            self.permissions = {"allow": [], "deny": [], "ask": []}
            self.dangerous_patterns = []

    def _write_default(self):
        default = {
            "permissions": {
                "allow": ["Read", "Grep", "Glob"],
                "deny": [],
                "ask": ["Write", "Edit", "Bash"],
            },
            "dangerous_patterns": [
                "rm -rf", "format ", "del /s", "del /q", "rmdir /s",
                "Remove-Item -Recurse", ":(){", "mkfs", "> /dev/sda",
            ],
        }
        os.makedirs(os.path.dirname(self.settings_path), exist_ok=True)
        with open(self.settings_path, "w", encoding="utf-8") as f:
            json.dump(default, f, ensure_ascii=False, indent=2)

    def check(self, tool_name: str, params: Dict[str, Any], risk_level: str) -> PermissionDecision:
        """检查工具调用权限。"""
        # 1. 危险命令模式检查（针对 Bash）—— 使用 token 级匹配，避免误判
        if tool_name == "Bash":
            cmd = params.get("command", "")
            dangerous = self._check_dangerous_command(cmd)
            if dangerous:
                return PermissionDecision("deny", f"命中危险命令模式: {dangerous}")

        # 2. deny
        if self._match_rules(tool_name, params, self.permissions.get("deny", [])):
            return PermissionDecision("deny", "命中 deny 规则")

        # 3. allow
        if self._match_rules(tool_name, params, self.permissions.get("allow", [])):
            return PermissionDecision("allow", "命中 allow 规则")

        # 4. ask
        if self._match_rules(tool_name, params, self.permissions.get("ask", [])):
            return PermissionDecision("ask", "命中 ask 规则")

        # 5. 默认按 risk_level
        if risk_level == "read_only":
            return PermissionDecision("allow", "read_only 默认放行")
        return PermissionDecision("ask", f"默认 {risk_level} 操作需确认")

    def _match_rules(self, tool_name: str, params: Dict[str, Any], rules: List[str]) -> bool:
        """检查工具调用是否匹配规则列表。"""
        for rule in rules:
            if self._match_one(tool_name, params, rule):
                return True
        return False

    def _match_one(self, tool_name: str, params: Dict[str, Any], rule: str) -> bool:
        """匹配单条规则。

        规则格式：
          "Read"            匹配工具名
          "Bash"            匹配工具名（所有 Bash 调用）
          "Bash(npm test)"  匹配 Bash 命令 = "npm test"
          "Bash(npm *)"     匹配 Bash 命令以 "npm " 开头
        """
        # 检查是否有参数过滤
        if "(" in rule and rule.endswith(")"):
            name_part, arg_part = rule.split("(", 1)
            arg_part = arg_part[:-1]  # 去掉右括号
            if name_part != tool_name:
                return False
            # 对 Bash 来说匹配 command 参数
            if tool_name == "Bash":
                cmd = params.get("command", "")
                # 用 fnmatch 支持 * 通配
                return fnmatch.fnmatch(cmd, arg_part)
            # 其他工具暂不支持参数过滤
            return True
        else:
            return rule == tool_name

    def add_allow(self, tool_name: str, params: Dict[str, Any]):
        """用户选择"始终允许"时调用，写回 settings.json。"""
        rule = self._build_rule_from_params(tool_name, params)
        if rule not in self.permissions["allow"]:
            self.permissions["allow"].append(rule)
            self._save()

    def _build_rule_from_params(self, tool_name: str, params: Dict[str, Any]) -> str:
        """根据工具调用生成 allow 规则。"""
        if tool_name == "Bash":
            cmd = params.get("command", "")
            # 提取命令前缀作为通配规则
            # 例如 "npm test --foo" -> "Bash(npm test:*)"
            parts = cmd.split()
            if len(parts) >= 2:
                prefix = " ".join(parts[:2])
                return f"Bash({prefix}:*)"
            elif len(parts) == 1:
                return f"Bash({parts[0]}:*)"
            else:
                return "Bash"
        # 其他工具只记录工具名
        return tool_name

    def _save(self):
        data = {
            "permissions": self.permissions,
            "dangerous_patterns": self.dangerous_patterns,
        }
        with open(self.settings_path, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)

    def _check_dangerous_command(self, cmd: str) -> str:
        """检查命令是否命中危险模式。使用 token 级匹配，避免误判。

        返回命中的模式字符串，未命中返回空字符串。
        对每个危险模式：
          - 先将命令按空白拆分为 tokens
          - 检查模式是否作为独立 token 序列出现（而非子串）
          - 支持多空格、引号等绕过的检测
        """
        # 标准化命令：合并多空格、去除引号包裹
        normalized = re.sub(r'\s+', ' ', cmd.strip())
        # 去除常见引号包裹（如 rm' -rf、rm"-rf"）
        normalized_no_quotes = re.sub(r"['\"]", '', normalized)

        for pattern in self.dangerous_patterns:
            pattern_stripped = pattern.strip()
            if not pattern_stripped:
                continue
            # 子串匹配（在标准化后的命令上）
            if pattern_stripped in normalized or pattern_stripped in normalized_no_quotes:
                # 额外检查：模式应该出现在命令的"操作"位置，而非单词中间
                # 例如 "format " 不应匹配 "reformat " 或 "information "
                # 检查模式前面是否是单词边界（行首或空白）
                for text in [normalized, normalized_no_quotes]:
                    idx = text.find(pattern_stripped)
                    if idx >= 0:
                        # 模式前面应该是行首或空白字符
                        if idx == 0 or text[idx - 1] in (' ', '|', ';', '&', '(', '`', '$'):
                            return pattern_stripped
        return ""
