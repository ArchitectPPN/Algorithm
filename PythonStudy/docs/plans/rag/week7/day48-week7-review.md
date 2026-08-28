# Day 48：Week7 复盘 + 预习 Week8（RAG 评估专题）+ LangGraph 了解级

> 目标：整理 Week7 学习成果，检验自测，预习 Week8（RAG 评估专题——把 Day 47 的人工评估自动化），
> 再花 2-3 小时了解 LangGraph 的定位（知道它和 LangChain 的区别即可，不深做）。

---

## 学习路线（约 3-4 小时）

```
整理笔记（30min）→ 检验自测（40min）→ 了解 LangGraph（2-3h）→ 预习 Week8（20min）
```

---

## 第一步：整理笔记（30min）

Week7 关键知识点串联：

1. **RAG 链组件化**（Day 42）：四步骨架（向量化→检索→拼 prompt→生成），前三步可控、第四步黑盒
2. **审查 Prompt 模板**（Day 43）：四要素（角色/上下文/格式/边界），编号引用为防幻觉埋钩子
3. **RAG 集成 Agent**（Day 44）：固定管道 vs Agent 检索，代码审查适合 Agent（先识别风险再针对性检索）
4. **引用校验治幻觉**（Day 45）：两道关（prompt 约束 + 代码校验），basis 编号核对
5. **LangChain RAG 对比**（Day 46）：LCEL 管道编排是核心价值，代价是抽象厚/调试难
6. **综合实战**（Day 47）：有 RAG vs 无 RAG 对比报告，量化数字

一条主线：**RAG 从"检索问答"升级成"Agent 的知识工具"，落地到代码审查场景，并用数字证明价值。**

---

## 第二步：检验自测（40min）

### 默写代码

- [ ] `RAGChain` 四步骨架（retrieve / build_prompt / _call_llm / ask）
- [ ] search_knowledge 工具的 TOOLS 描述（function calling schema）
- [ ] `verify_citations()` 的三条校验规则

### 一句话讲清楚

- [ ] 固定 RAG 管道和 Agent 检索模式的三个差异？
- [ ] 代码审查为什么适合 Agent 检索模式？
- [ ] RAG 防幻觉的"两道关"各管什么？
- [ ] LangChain 的 LCEL 封装了什么？代价是什么？
- [ ] basis=null 的设计意图？

### 回答追问

- [ ] Agent 模式比固定管道贵在哪？什么时候不值？
- [ ] 模型输出 basis="[5]" 但只检索了 3 条，校验器怎么识别？
- [ ] 张冠李戴（编号存在但来源不对）为什么比超范围编号难校验？
- [ ] Day 47 的对比实验，RAG 在哪类代码上价值最大？哪类不值？
- [ ] 为什么选裸写而不是 LangChain 做 RAG？（面试角度）

---

## 第三步：了解 LangGraph（2-3h）

> 定位：了解级，知道它是什么、和 LangChain 什么关系、什么场景用。不写复杂代码。

### 核心概念（读官方文档 + 跑一个最小 demo）

| 概念 | 一句话理解 | LangChain 对应 |
|------|----------|---------------|
| StateGraph | 状态机：节点间传递共享 state | Chain（链式管道） |
| Node | 一个处理步骤（函数：state→state） | Chain 的一环 |
| Edge | 节点间的跳转（可带条件） | LCEL `\|`（固定顺序） |
| Conditional Edge | 根据 state 决定跳哪个节点 | Chain 做不到（要 if/else 手写） |

### 关键认知

1. **LangGraph 是 LangChain 的继任者**，解决 LangChain Chain 只能"固定管道"的局限：
   Agent 需要"根据结果决定下一步"（循环、分支），Chain 表达不了，Graph 可以。
2. **本质区别**：Chain = 有向无环管道（A→B→C）；Graph = 状态机（A→B→根据结果→A 或 C，可循环）。
3. **你的裸写 ReAct loop（Day 44）其实就是个状态图**：
   思考→有工具调用？→（是）执行工具→思考 / （否）结束。
   LangGraph 把这个循环用 StateGraph 显式画出来——你裸写用 while 循环表达的是同一件事。

### 最小 demo（可选跑）

```python
# myagent/langchain/langgraph_minimal.py（了解级，跑通即可）
from langgraph.graph import StateGraph, END
from typing import TypedDict

class State(TypedDict):
    query: str
    retrieved: list
    answer: str

def retrieve(state): ...      # 调 RAGChain.retrieve
def generate(state): ...      # 调 LLM 生成
def decide(state):            # 条件边
    return "generate" if state["retrieved"] else END

g = StateGraph(State)
g.add_node("retrieve", retrieve)
g.add_node("generate", generate)
g.set_entry_point("retrieve")
g.add_conditional_edges("retrieve", decide)
g.add_edge("generate", END)
app = g.compile()
```

> 跑通后对比：你的裸写 RAGChain.ask() 用 20 行 while 循环做了同样的事，
> LangGraph 用 StateGraph 画出来——**结构更清晰但代码更多**。
> 结论：简单流程裸写够，复杂多分支流程（多工具 + 人审节点 + 回退）才值得上 LangGraph。

---

## 第四步：预习 Week8（20min）

Week8 主题：**RAG 评估专题**（transition-plan 第 8 周，新增的重点补 AI 侧）

核心问题：
- Day 47 我们用"人工看 10 段代码的审查结果"评估——**不可复现、不可规模化**，面试会问"你怎么客观量化系统质量"
- Week8 要把人工评估升级成**自动评估**：
  - 检索质量：Recall@K、MRR（Day 39 做过，扩展到审查场景）
  - 生成质量：faithfulness（忠实度——回答是否基于检索片段）、answer relevance
  - 工具：ragas 或自写评估脚本
  - LLM-as-Judge：用另一个模型给审查结果打分

**思考题：** Day 47 的对比报告里，哪些指标可以自动化？哪些暂时还得人工？
（faithfulness 能自动——用 LLM 判断"回答是否每条都挂在检索片段上"；
"引用准确率"能自动——Day 45 校验器已做了一半。）

---

## Week7 产出总览

| 文件 | 说明 |
|------|------|
| `rag/rag_chain.py` | 独立 RAGChain 组件 |
| `rag/review_prompts.py` | 审查 Prompt 模板（有/无 RAG 两套） |
| `rag/citation_check.py` | 引用校验器 |
| `myagent/rag_agent_practice.py` | RAG 增强审查 Agent |
| `data/test_code/*.py` | 10 段测试代码 |
| `myagent/langchain/rag_langchain_compare.py` | LangChain RAG 对比 |
| `myagent/langchain/langgraph_minimal.py` | LangGraph 最小 demo（可选） |
| `api/rag_service.py`（扩展） | 新增 /review 端点 |
| `rag/run_eval_batch.py` + `eval_results_day47.json` | 批量评估脚本 + 结果 |
| `docs/plans/rag/week7/compare-report.md` | 有 RAG vs 无 RAG 对比报告 |
| `docs/plans/rag/week7/day*.md` | 每日学习教程 |

## 面试一句话总结本周

"第 7 周把 RAG 从检索问答升级成 Agent 的知识工具，落地到规范驱动的代码审查场景：
裸写 RAGChain + ReAct loop 让 Agent 自主检索规范，用编号引用 + 代码校验把规范幻觉率控制在 X% 以下，
10 段代码对照实验显示 RAG 让埋雷检出率从 Y% 提升到 Z%。"
