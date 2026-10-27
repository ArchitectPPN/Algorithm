/**
 * terminate 演示：四种跑法，只差一个字段
 *
 * 运行：node docs/pi/demos/terminate-demo.mjs
 *
 * 这个文件只做一件事：把 Pi 的 agent-loop 里与 terminate 有关的那几行，
 * 原样复刻成一个 60 行的循环，然后用一个"假模型"驱动它跑四遍。
 *
 * 对应的源码位置：
 *   agent-loop.ts:281   hasMoreToolCalls = !executedToolBatch.terminate
 *   agent-loop.ts:700   finalizedCalls.every((f) => f.result.terminate === true)
 *   agent-loop.ts:512   截断批强制 return { terminate: false }
 *
 * 没有 pi 的依赖，node >= 18 直接跑。
 */

const C = {
	reset: "\x1b[0m",
	dim: "\x1b[2m",
	bold: "\x1b[1m",
	green: "\x1b[32m",
	red: "\x1b[31m",
	cyan: "\x1b[36m",
	yellow: "\x1b[33m",
};

// ─────────────────────────────────────────────────────────────
// 1. 两个工具。差别只有 submit_review 返回对象里那一个字段
// ─────────────────────────────────────────────────────────────

const readFileTool = {
	name: "read_file",
	execute: async ({ path }) => ({
		content: [{ type: "text", text: `# Pi\n一个极薄的 agent harness。（来自 ${path}）` }],
		// 没有 terminate 字段 —— 也就是说它是 undefined
	}),
};

// 工厂：用来生成"同一工具、return 里带不带 terminate"的两个版本
const submitReview = (terminate) => ({
	name: "submit_review",
	execute: async ({ findings }) => {
		const result = {
			content: [{ type: "text", text: `已提交 ${findings.length} 条评审结论` }],
			details: { findings },
		};
		// ↓↓↓ 整个演示里唯一的差别就是这一行 ↓↓↓
		if (terminate) result.terminate = true;
		return result;
	},
});

// ─────────────────────────────────────────────────────────────
// 2. 极简复刻 Pi 的 loop（只保留与 terminate 有关的分支）
// ─────────────────────────────────────────────────────────────

async function runLoop({ label, userText, tools, script, log }) {
	log(`\n${C.bold}${C.cyan}════ ${label} ════${C.reset}`);
	log(`${C.dim}用户说：${userText}${C.reset}`);

	const messages = [{ role: "user", content: userText }];
	let requestNo = 0;

	// 外层 while(true)：本演示不涉及 follow-up，只跑一圈
	while (true) {
		let hasMoreToolCalls = false;
		requestNo++;

		// —— 假模型：按脚本返回下一条 assistant 消息 ——
		const assistant = script(requestNo, messages);
		messages.push(assistant);

		const calls = assistant.content.filter((c) => c.type === "toolCall");
		log(`\n${C.yellow}[请求 ${requestNo}]${C.reset} 模型返回：${
			calls.length ? calls.map((c) => `${c.name}(${JSON.stringify(c.args)})`).join(" + ") : `纯文本`
		}`);

		// 没有工具调用 = 天然收敛，循环结束
		if (calls.length === 0) {
			log(`         无工具调用 → hasMoreToolCalls = false → 结束`);
			return finish({ messages, requestNo, log, finalText: assistant.content.map((c) => c.text).join("") });
		}

		// —— 逐个执行（Pi 的并行批也走同一聚合规则）——
		const executed = [];
		for (const call of calls) {
			const tool = tools.find((t) => t.name === call.name);
			const result = await tool.execute(call.args);
			executed.push({ call, result });
			const raw = JSON.stringify({ content: result.content, details: result.details, terminate: result.terminate });
			log(`         执行 ${call.name} → ${C.dim}${raw}${C.reset}`);
			log(
				`         该结果的 terminate = ${
					result.terminate === undefined ? `${C.red}undefined${C.reset}` : `${C.green}${result.terminate}${C.reset}`
				}`,
			);
		}

		// —— 就是这一行：agent-loop.ts:700-702 ——
		const batchTerminate = executed.length > 0 && executed.every((e) => e.result.terminate === true);
		log(
			`         批末聚合 every([${executed.map((e) => String(e.result.terminate)).join(", ")}]) → ${
				batchTerminate ? `${C.green}true${C.reset}` : `${C.red}false${C.reset}`
			}`,
		);

		for (const e of executed) {
			messages.push({ role: "toolResult", toolName: e.call.name, content: e.result.content });
		}

		// —— 还有这一行：agent-loop.ts:281 ——
		hasMoreToolCalls = !batchTerminate;
		log(`         hasMoreToolCalls = !${batchTerminate} = ${hasMoreToolCalls}`);

		if (!hasMoreToolCalls) {
			log(`         ${C.green}→ 不再发请求，循环结束${C.reset}`);
			return finish({ messages, requestNo, log, finalText: null });
		}
	}
}

function finish({ messages, requestNo, log, finalText }) {
	log(`\n${C.dim}── 结果 ──${C.reset}`);
	log(`         实际发出的模型请求次数：${C.bold}${requestNo}${C.reset}`);
	log(
		`         最后一条消息的 role：${C.bold}${messages[messages.length - 1].role}${C.reset}`,
	);
	log(
		`         用户看到的那句总结：${
			finalText ? `${C.green}"${finalText}"${C.reset}` : `${C.red}不存在${C.reset}`
		}`,
	);
	const anyTerminateInLog = JSON.stringify(messages).includes("terminate");
	log(
		`         会话记录里能否找到 terminate 字段：${anyTerminateInLog ? "能" : `${C.red}找不到（runtime-only）${C.reset}`}`,
	);
	return { requestNo, finalText };
}

// ─────────────────────────────────────────────────────────────
// 3. 四种跑法
// ─────────────────────────────────────────────────────────────

const noop = () => {};
const log = (s) => console.log(s);

// 剧本 1：读文件 → 总结
const scriptRead = (n) =>
	n === 1
		? { role: "assistant", content: [{ type: "toolCall", id: "c1", name: "read_file", args: { path: "README.md" } }] }
		: { role: "assistant", content: [{ type: "text", text: "README 讲的是：Pi 是一个极薄的 agent harness。" }] };

// 剧本 2：提交评审 → 说一句废话
const scriptSubmit = (n) =>
	n === 1
		? {
				role: "assistant",
				content: [
					{ type: "toolCall", id: "c1", name: "submit_review", args: { findings: ["SQL 注入", "明文密码"] } },
				],
			}
		: { role: "assistant", content: [{ type: "text", text: "好的，以上就是本次评审结论。" }] };

// 剧本 3：一次吐两个调用（混合批）
const scriptMixed = (n) =>
	n === 1
		? {
				role: "assistant",
				content: [
					{ type: "toolCall", id: "c1", name: "read_file", args: { path: "app.py" } },
					{ type: "toolCall", id: "c2", name: "submit_review", args: { findings: ["SQL 注入"] } },
				],
			}
		: { role: "assistant", content: [{ type: "text", text: "看完了，另外这次评审的结论也一并提交了。" }] };

const out = [];

out.push(
	await runLoop({
		label: "场景 A · read_file，结果里没有 terminate 字段",
		userText: "看下 README 写了什么",
		tools: [readFileTool],
		script: scriptRead,
		log,
	}),
);

out.push(
	await runLoop({
		label: "场景 B · submit_review，返回 terminate: true",
		userText: "把评审结论以 JSON 给我",
		tools: [submitReview(true)],
		script: scriptSubmit,
		log,
	}),
);

out.push(
	await runLoop({
		label: "场景 C · 同一个 submit_review，把 true 改成不写（对照实验）",
		userText: "把评审结论以 JSON 给我",
		tools: [submitReview(false)],
		script: scriptSubmit,
		log,
	}),
);

out.push(
	await runLoop({
		label: "场景 D · 混合批：一个不打、一个打 true",
		userText: "看下 app.py，顺手把评审结论给我",
		tools: [readFileTool, submitReview(true)],
		script: scriptMixed,
		log,
	}),
);

// ─────────────────────────────────────────────────────────────
// 4. 汇总
// ─────────────────────────────────────────────────────────────

console.log(`\n${C.bold}${C.cyan}════ 汇总 ════${C.reset}`);
console.log("场景                          请求次数   用户看到总结");
const names = ["A 无字段(read)", "B true(submit)", "C false(submit)", "D 混合批"];
out.forEach((r, i) => {
	console.log(
		`${names[i].padEnd(28)} ${String(r.requestNo).padEnd(10)} ${r.finalText ? "有" : `${C.red}无${C.reset}`}`,
	);
});
console.log(`\n${C.dim}B 与 C 的区别，只有 execute() 返回对象里那一个字面量。${C.reset}`);
