# Git 协作规范

## 分支策略

- main/master：生产分支，禁止直接提交
- develop：开发分支，日常开发基准
- feature/xxx：功能分支，从 develop 拉出，完成后合并回 develop
- bugfix/xxx：修复分支，从 develop 拉出
- hotfix/xxx：紧急修复，从 main 拉出，修复后合并回 main 和 develop
- 分支命名：`feature/CRMW-12345-用户登录优化`（JIRA编号+简短描述）

## Commit Message 规范

- 格式：`<JIRA编号> <简短描述>`
- 简短描述用中文，动词开头（新增/修改/修复/重构/移除/优化）
- 复杂改动加详细说明，用无序列表
- 示例：`CRMW-12345 修复用户登录校验逻辑`
- 禁止无意义的提交信息，如 "fix"、"update"、"wip"

## PR 流程

- 功能开发完成后，从 feature 分支向 develop 发起 PR
- PR 描述必须包含：改动内容、影响范围、测试情况
- 至少 1 人 Code Review 通过后才能合并
- 合并前确保 CI 通过（lint、测试、构建）
- 合并后删除 feature 分支，保持分支列表整洁

## 代码审查重点

- 逻辑正确性：边界条件、空指针、异常处理
- 安全性：SQL 注入、XSS、敏感信息泄露
- 性能：N+1 查询、不必要的循环、内存泄漏
- 代码风格：命名规范、注释完整性、代码重复
- 测试覆盖：是否需要补充单元测试

## 常见问题

- 不要在 feature 分支上 rebase develop，用 merge 保持历史完整
- 不要 force push 到共享分支（develop/main）
- 冲突解决要在本地完成，不要在 PR 界面直接解决
- 大改动拆成多个小 PR，每个 PR 只做一件事
