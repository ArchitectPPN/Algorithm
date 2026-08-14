# 前端安全规范

## XSS 跨站脚本防护

- 渲染用户内容时使用转义，禁止用 innerHTML 直接插入用户输入
- React/Vue 默认转义，但 v-html / dangerouslySetInnerHTML 要禁用
- 富文本编辑器内容必须经过白名单过滤（如 DOMPurify）
- 警惕 URL 注入：`javascript:` 开头的链接禁止跳转

## CSRF 跨站请求伪造防护

- 状态变更请求使用 POST，并校验 CSRF Token
- 设置 SameSite=Lax 或 Strict Cookie 属性
- 校验请求来源：检查 Origin / Referer 头
- 双重提交 Cookie 方案：请求头携带与 Cookie 一致的 Token

## 敏感信息保护

- 前端不要存储密钥，Token 放内存或 HttpOnly Cookie
- 不要在 localStorage 存用户敏感信息（会被 JS 读取）
- 表单提交的密码/手机号字段要脱敏后再回显
- 前端混淆不是安全，真正的安全在服务端校验

## 输入校验

- 前端校验是体验优化，后端校验才是安全底线
- 对上传文件校验类型、大小、内容签名，防止恶意文件
- 限制输入长度，防止超大字段拖慢渲染
- 特殊字符（`<>"'&`）在拼接 HTML 前必须转义

## 依赖安全

- 定期检查 npm/pnpm 依赖漏洞（npm audit / Snyk）
- 锁定依赖版本，避免自动升级引入漏洞
- 第三方 CDN 资源要加 SRI 完整性校验
- 版本更新日志要关注安全公告（CVE）
