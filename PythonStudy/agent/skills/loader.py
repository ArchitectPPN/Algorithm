"""技能加载器。

技能文件格式（Markdown + frontmatter）：
---
name: review
description: 代码审查技能...
---

（技能 prompt 内容）

触发方式：
1. 用户输入 /review <内容> -> 手动触发
2. 自动匹配：每轮对话前把所有技能 name+description 告诉模型，模型决定是否激活
"""

import os
import re
from dataclasses import dataclass
from typing import Dict, List, Optional


@dataclass
class Skill:
    name: str               # 触发名（如 review）
    description: str        # 给模型看的描述
    content: str            # 技能 prompt 内容
    file_path: str          # 源文件路径


class SkillLoader:
    def __init__(self, skills_dir: str):
        self.skills_dir = skills_dir
        self.skills: Dict[str, Skill] = {}
        self.load()

    def load(self):
        self.skills.clear()
        if not os.path.exists(self.skills_dir):
            return
        for fname in os.listdir(self.skills_dir):
            if not fname.endswith(".md"):
                continue
            fpath = os.path.join(self.skills_dir, fname)
            try:
                skill = self._parse_file(fpath)
                if skill:
                    self.skills[skill.name] = skill
            except Exception as e:
                print(f"  [⚠️ 技能] 解析 {fname} 失败: {e}")

    def _parse_file(self, fpath: str) -> Optional[Skill]:
        with open(fpath, "r", encoding="utf-8") as f:
            text = f.read()

        # 解析 frontmatter
        m = re.match(r"^---\s*\n(.*?)\n---\s*\n(.*)$", text, re.DOTALL)
        if not m:
            return None
        frontmatter = m.group(1)
        content = m.group(2).strip()

        # 解析字段
        name = ""
        description = ""
        for line in frontmatter.split("\n"):
            line = line.strip()
            if line.startswith("name:"):
                name = line[5:].strip()
            elif line.startswith("description:"):
                description = line[12:].strip()

        if not name:
            # 用文件名作为 name
            name = os.path.splitext(os.path.basename(fpath))[0]

        return Skill(name=name, description=description, content=content, file_path=fpath)

    def list_skills(self) -> List[Skill]:
        return list(self.skills.values())

    def get(self, name: str) -> Optional[Skill]:
        return self.skills.get(name)

    def match_trigger(self, user_input: str) -> Optional[Skill]:
        """检查用户输入是否以 /skill-name 开头，是则返回对应技能。"""
        stripped = user_input.strip()
        if not stripped.startswith("/"):
            return None
        # 提取命令名（/ 后到空格或行尾）
        m = re.match(r"/(\S+)", stripped)
        if not m:
            return None
        cmd = m.group(1)
        return self.skills.get(cmd)

    def build_skills_hint(self) -> str:
        """生成给模型看的技能列表提示，用于自动匹配。"""
        if not self.skills:
            return ""
        lines = ["[可用技能] 以下技能可被激活，若用户请求匹配某技能描述，请在回复中第一行输出 `[SKILL:技能名]` 来激活："]
        for skill in self.skills.values():
            lines.append(f"- {skill.name}: {skill.description}")
        return "\n".join(lines)

    def extract_activation(self, text: str) -> Optional[str]:
        """从模型回复中提取 [SKILL:xxx] 激活标记。"""
        m = re.search(r"\[SKILL:(\S+?)\]", text)
        if m:
            return m.group(1)
        return None

    def reload(self):
        self.load()
        return len(self.skills)
