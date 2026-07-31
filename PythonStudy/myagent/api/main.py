"""
FastAPI 学习 Demo —— Day 22
路由基础 + Pydantic + TODO CRUD
"""
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime

app = FastAPI(title="Git Agent API", version="0.1.0")

# ══════════════════════════════════════════════════════════
# 1. Hello World — 最简单的路由
# ══════════════════════════════════════════════════════════

@app.get("/")
def root():
    return {"message": "Hello World", "time": datetime.now().isoformat()}

# ══════════════════════════════════════════════════════════
# 2. 路径参数 + 查询参数
# ══════════════════════════════════════════════════════════

@app.get("/hello/{name}")
def hello(name: str, greeting: str = "你好"):
    """路径参数 name，查询参数 greeting"""
    return {"message": f"{greeting}，{name}！"}

# ══════════════════════════════════════════════════════════
# 3. Pydantic 模型 + POST 请求体
# ══════════════════════════════════════════════════════════

class TodoItem(BaseModel):
    """待办事项"""
    title: str = Field(..., description="标题", min_length=1, max_length=100)
    done: bool = False
    priority: int = Field(default=1, ge=1, le=5, description="优先级 1-5")

class TodoResponse(BaseModel):
    """待办事项响应（含 id 和时间）"""
    id: int
    title: str
    done: bool
    priority: int
    created_at: str

# 模拟数据库
todos: list[TodoResponse] = []
_next_id = 1

@app.post("/todos", response_model=TodoResponse)
def create_todo(item: TodoItem):
    """创建待办事项"""
    global _next_id
    todo = TodoResponse(
        id=_next_id,
        title=item.title,
        done=item.done,
        priority=item.priority,
        created_at=datetime.now().isoformat()
    )
    todos.append(todo)
    _next_id += 1
    return todo

@app.get("/todos", response_model=list[TodoResponse])
def list_todos(done: Optional[bool] = None):
    """列出所有待办事项，可按完成状态筛选"""
    if done is None:
        return todos
    return [t for t in todos if t.done == done]

@app.get("/todos/{todo_id}", response_model=TodoResponse)
def get_todo(todo_id: int):
    """获取单个待办事项"""
    for t in todos:
        if t.id == todo_id:
            return t
    raise HTTPException(status_code=404, detail=f"待办事项 {todo_id} 不存在")

@app.put("/todos/{todo_id}", response_model=TodoResponse)
def update_todo(todo_id: int, item: TodoItem):
    """更新待办事项"""
    for t in todos:
        if t.id == todo_id:
            t.title = item.title
            t.done = item.done
            t.priority = item.priority
            return t
    raise HTTPException(status_code=404, detail=f"待办事项 {todo_id} 不存在")

@app.delete("/todos/{todo_id}")
def delete_todo(todo_id: int):
    """删除待办事项"""
    for i, t in enumerate(todos):
        if t.id == todo_id:
            del todos[i]
            return {"message": f"待办事项 {todo_id} 已删除"}
    raise HTTPException(status_code=404, detail=f"待办事项 {todo_id} 不存在")

# ══════════════════════════════════════════════════════════
# 4. Agent 端点 —— 把 Git Agent 包装成 API
# ══════════════════════════════════════════════════════════

from .agent_service import run_agent

class ReviewRequest(BaseModel):
    """Agent 请求"""
    question: str = Field(..., description="用户问题", min_length=1, max_length=1000)
    repo_path: str = Field(default=".", description="仓库路径，默认当前目录")

class ToolCallRecord(BaseModel):
    """单次工具调用记录"""
    tool: str
    args: dict
    result_preview: str

class ReviewResponse(BaseModel):
    """Agent 响应"""
    answer: str
    tool_calls: list[ToolCallRecord] = []
    loops: int
    error: Optional[str] = None

@app.post("/review", response_model=ReviewResponse)
def review_code(req: ReviewRequest):
    """
    Git Agent 代码审查。

    调用 ReAct Agent 分析仓库：git status / git log / git diff / read_file。
    """
    result = run_agent(question=req.question, repo_path=req.repo_path)
    return ReviewResponse(**result)

@app.get("/health")
def health():
    """健康检查"""
    return {"status": "ok", "time": datetime.now().isoformat()}

# ══════════════════════════════════════════════════════════
# 启动：uvicorn api.main:app --reload --port 8000
# 文档：http://localhost:8000/docs
# ══════════════════════════════════════════════════════════
