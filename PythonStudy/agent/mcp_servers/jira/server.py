#!/usr/bin/env python3
"""Jira MCP Server - 支持 Jira Server/DC 的 MCP 工具服务"""
import json
import sys
import os
import urllib.request
import urllib.parse
import base64
import ssl

# 自身目录，用于定位 config.json
SERVER_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(SERVER_DIR, 'config.json')

def load_config():
    """加载自身目录下的 config.json"""
    if not os.path.exists(CONFIG_FILE):
        default = {"base_url": "", "username": "", "password": ""}
        with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
            json.dump(default, f, ensure_ascii=False, indent=2)
    with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
        return json.load(f)


class JiraMCPServer:
    def __init__(self):
        self.base_url = ""
        self.auth_header = ""
        self.initialized = False

    def _init_from_config(self):
        """从 config.json 读取连接配置"""
        cfg = load_config()
        self.base_url = cfg.get("base_url", "")
        username = cfg.get("username", "")
        password = cfg.get("password", "")
        if username and password:
            cred = base64.b64encode(f"{username}:{password}".encode()).decode()
            self.auth_header = f"Basic {cred}"
        self.initialized = True

    def _request(self, method, path, data=None):
        """发送 HTTP 请求到 Jira REST API"""
        url = f"{self.base_url.rstrip('/')}/rest/api/2/{path.lstrip('/')}"
        headers = {
            "Authorization": self.auth_header,
            "Content-Type": "application/json",
            "Accept": "application/json"
        }
        body = json.dumps(data).encode() if data else None
        req = urllib.request.Request(url, data=body, headers=headers, method=method)
        # 允许自签名证书
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        try:
            with urllib.request.urlopen(req, context=ctx, timeout=30) as resp:
                return json.loads(resp.read().decode())
        except urllib.error.HTTPError as e:
            error_body = e.read().decode() if e.fp else ""
            return {"error": f"HTTP {e.code}: {error_body[:500]}"}
        except Exception as e:
            return {"error": str(e)}

    def _get(self, path):
        return self._request("GET", path)

    def _post(self, path, data):
        return self._request("POST", path, data)

    def _put(self, path, data):
        return self._request("PUT", path, data)

    # ==================== 工具定义 ====================
    def get_tools(self):
        return [
            {
                "name": "jira_search",
                "description": "使用 JQL 搜索 Jira Issue（支持所有 JQL 语法）",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "jql": {"type": "string", "description": "JQL 查询语句，如 'project = PROJ AND status = Open'"},
                        "max_results": {"type": "integer", "description": "最大返回数量，默认 20", "default": 20}
                    },
                    "required": ["jql"]
                }
            },
            {
                "name": "jira_get_issue",
                "description": "获取 Jira Issue 详情（包括描述、状态、负责人、评论等）",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "issue_key": {"type": "string", "description": "Issue Key，如 PROJ-123"}
                    },
                    "required": ["issue_key"]
                }
            },
            {
                "name": "jira_create_issue",
                "description": "创建新的 Jira Issue",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "project_key": {"type": "string", "description": "项目 Key，如 PROJ"},
                        "summary": {"type": "string", "description": "Issue 标题"},
                        "description": {"type": "string", "description": "Issue 描述"},
                        "issue_type": {"type": "string", "description": "Issue 类型，如 Bug、Task、Story", "default": "Task"}
                    },
                    "required": ["project_key", "summary"]
                }
            },
            {
                "name": "jira_add_comment",
                "description": "给 Jira Issue 添加评论",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "issue_key": {"type": "string", "description": "Issue Key，如 PROJ-123"},
                        "comment": {"type": "string", "description": "评论内容"}
                    },
                    "required": ["issue_key", "comment"]
                }
            },
            {
                "name": "jira_transition_issue",
                "description": "变更 Jira Issue 的状态（如 Open → In Progress → Done）",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "issue_key": {"type": "string", "description": "Issue Key，如 PROJ-123"},
                        "transition_name": {"type": "string", "description": "目标状态名称，如 'In Progress'、'Done'"}
                    },
                    "required": ["issue_key", "transition_name"]
                }
            },
            {
                "name": "jira_assign_issue",
                "description": "分配 Jira Issue 给指定用户",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "issue_key": {"type": "string", "description": "Issue Key，如 PROJ-123"},
                        "username": {"type": "string", "description": "用户名"}
                    },
                    "required": ["issue_key", "username"]
                }
            },
            {
                "name": "jira_list_projects",
                "description": "列出所有可见的 Jira 项目",
                "inputSchema": {
                    "type": "object",
                    "properties": {}
                }
            },
            {
                "name": "jira_get_project",
                "description": "获取 Jira 项目详情（包括 Issue 类型、负责人等）",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "project_key": {"type": "string", "description": "项目 Key，如 PROJ"}
                    },
                    "required": ["project_key"]
                }
            }
        ]

    # ==================== 工具执行 ====================
    def call_tool(self, name, arguments):
        if name == "jira_search":
            return self._tool_search(arguments)
        elif name == "jira_get_issue":
            return self._tool_get_issue(arguments)
        elif name == "jira_create_issue":
            return self._tool_create_issue(arguments)
        elif name == "jira_add_comment":
            return self._tool_add_comment(arguments)
        elif name == "jira_transition_issue":
            return self._tool_transition_issue(arguments)
        elif name == "jira_assign_issue":
            return self._tool_assign_issue(arguments)
        elif name == "jira_list_projects":
            return self._tool_list_projects()
        elif name == "jira_get_project":
            return self._tool_get_project(arguments)
        else:
            return {"error": f"未知工具: {name}"}

    def _tool_search(self, args):
        jql = args["jql"]
        max_results = args.get("max_results", 20)
        encoded_jql = urllib.parse.quote(jql)
        result = self._get(f"search?jql={encoded_jql}&maxResults={max_results}&fields=summary,status,assignee,priority,created,updated,issuetype")
        if "error" in result:
            return result
        issues = []
        for item in result.get("issues", []):
            fields = item.get("fields", {})
            issues.append({
                "key": item["key"],
                "summary": fields.get("summary", ""),
                "status": fields.get("status", {}).get("name", ""),
                "assignee": (fields.get("assignee") or {}).get("displayName", "未分配"),
                "priority": (fields.get("priority") or {}).get("name", ""),
                "type": fields.get("issuetype", {}).get("name", ""),
                "updated": fields.get("updated", "")
            })
        return {"total": result.get("total", 0), "issues": issues}

    def _tool_get_issue(self, args):
        key = args["issue_key"]
        result = self._get(f"issue/{key}?fields=summary,description,status,assignee,reporter,priority,created,updated,comment,issuetype,labels,components")
        if "error" in result:
            return result
        fields = result.get("fields", {})
        comments = []
        for c in fields.get("comment", {}).get("comments", [])[-5:]:
            comments.append({
                "author": c.get("author", {}).get("displayName", ""),
                "body": c.get("body", "")[:500],
                "created": c.get("created", "")
            })
        return {
            "key": result["key"],
            "summary": fields.get("summary", ""),
            "description": (fields.get("description") or "")[:2000],
            "status": fields.get("status", {}).get("name", ""),
            "type": fields.get("issuetype", {}).get("name", ""),
            "assignee": (fields.get("assignee") or {}).get("displayName", "未分配"),
            "reporter": (fields.get("reporter") or {}).get("displayName", ""),
            "priority": (fields.get("priority") or {}).get("name", ""),
            "labels": fields.get("labels", []),
            "created": fields.get("created", ""),
            "updated": fields.get("updated", ""),
            "comments": comments
        }

    def _tool_create_issue(self, args):
        data = {
            "fields": {
                "project": {"key": args["project_key"]},
                "summary": args["summary"],
                "issuetype": {"name": args.get("issue_type", "Task")}
            }
        }
        if args.get("description"):
            data["fields"]["description"] = args["description"]
        return self._post("issue", data)

    def _tool_add_comment(self, args):
        return self._post(f"issue/{args['issue_key']}/comment", {"body": args["comment"]})

    def _tool_transition_issue(self, args):
        key = args["issue_key"]
        target = args["transition_name"].lower()
        transitions = self._get(f"issue/{key}/transitions")
        if "error" in transitions:
            return transitions
        for t in transitions.get("transitions", []):
            if t["name"].lower() == target or t["to"]["name"].lower() == target:
                return self._post(f"issue/{key}/transitions", {"transition": {"id": t["id"]}})
        available = [t["name"] for t in transitions.get("transitions", [])]
        return {"error": f"未找到状态 '{args['transition_name']}'，可用状态: {available}"}

    def _tool_assign_issue(self, args):
        return self._put(f"issue/{args['issue_key']}/assignee", {"name": args["username"]})

    def _tool_list_projects(self):
        result = self._get("project")
        if isinstance(result, list):
            return [{"key": p["key"], "name": p["name"], "lead": p.get("lead", {}).get("displayName", "")} for p in result]
        return result

    def _tool_get_project(self, args):
        result = self._get(f"project/{args['project_key']}")
        if "error" in result:
            return result
        return {
            "key": result["key"],
            "name": result["name"],
            "description": (result.get("description") or "")[:500],
            "lead": result.get("lead", {}).get("displayName", ""),
            "issueTypes": [t["name"] for t in result.get("issueTypes", [])]
        }

    # ==================== MCP JSON-RPC 协议 ====================
    def handle_message(self, msg):
        method = msg.get("method", "")
        msg_id = msg.get("id")
        params = msg.get("params", {})

        if method == "initialize":
            self._init_from_config()
            return {
                "jsonrpc": "2.0", "id": msg_id,
                "result": {
                    "protocolVersion": "2024-11-05",
                    "capabilities": {"tools": {"listChanged": False}},
                    "serverInfo": {"name": "jira-mcp-server", "version": "1.0.0"}
                }
            }
        elif method == "notifications/initialized":
            return None
        elif method == "tools/list":
            return {
                "jsonrpc": "2.0", "id": msg_id,
                "result": {"tools": self.get_tools()}
            }
        elif method == "tools/call":
            tool_name = params.get("name", "")
            arguments = params.get("arguments", {})
            result = self.call_tool(tool_name, arguments)
            return {
                "jsonrpc": "2.0", "id": msg_id,
                "result": {
                    "content": [{"type": "text", "text": json.dumps(result, ensure_ascii=False, indent=2)}]
                }
            }
        elif method == "ping":
            return {"jsonrpc": "2.0", "id": msg_id, "result": {}}
        else:
            return {
                "jsonrpc": "2.0", "id": msg_id,
                "error": {"code": -32601, "message": f"Method not found: {method}"}
            }

    def run(self):
        """stdio 模式运行 MCP Server"""
        while True:
            try:
                line = sys.stdin.readline()
                if not line:
                    break
                line = line.strip()
                if not line:
                    continue
                msg = json.loads(line)
                response = self.handle_message(msg)
                if response is not None:
                    sys.stdout.write(json.dumps(response, ensure_ascii=False) + "\n")
                    sys.stdout.flush()
            except json.JSONDecodeError:
                continue
            except KeyboardInterrupt:
                break
            except Exception as e:
                err = {"jsonrpc": "2.0", "id": None, "error": {"code": -32603, "message": str(e)}}
                sys.stdout.write(json.dumps(err) + "\n")
                sys.stdout.flush()


if __name__ == "__main__":
    server = JiraMCPServer()
    server.run()
