/**
 * terminate: true 到底是从哪儿写进去的
 *
 * 运行：node docs/pi/demos/terminate-sources.mjs
 *
 * 前一个 demo（terminate-demo.mjs）只演示了"工具自己 return true"这一种来源，
 * 并且用的是为演示而编的场景。这个 demo 换一个角度：
 *
 *   把 Pi 里能写入 terminate 的三个入口全部显式建模，
 *   每个 terminate: true 都打印出它是谁写的、依据什么判断写的，
 *   并且全部换成真实 agent 里会遇到的场景。
 *
 * 对应源码（packages/agent/src/agent-loop.ts）：
 *   L757-758  beforeToolCall 返回 { block: true, terminate: true } → 写进错误结果
 *   L592/668  工具 execute() 自己返回 result.terminate
 *   L902      afterToolCall 返回 { terminate: ... } → 覆盖原值
 *   L701      every((r) => r.result.terminate === true) → 批级聚合
 *   L281      hasMoreToolCalls = !executedToolBatch.terminate
 *
 * 零依赖，node >= 18 直接跑。
 */

const C = {
	reset: "\x1b[0m",
	dim: "\x1b[2m",
	bold: "\x1b[1m",
	green: "\x1b[32m",
	red: "\x1b[31m",
	cyan: "\x1b[36m",
	yellow: "\x1b[33m",
	magenta: "\x1b[35m",
};

const log = (s = "") => console.log(s);
const shrink = (o, n = 46) => {
	const s = JSON.stringify(o);
	return s.length > n ? `${s.slice(0, n)}…` : s;
};

// ───────────────────────────────────────────────────────────────────────
// 工具集：全部是真实 agent 里会有的工具
// ───────────────────────────────────────────────────────────────────────

const readFile = {
	name: "read_file",
	execute: async ({ path }) => ({
		content: [{ type: "text", text: `# README\nPi 是一个极薄的 agent harness。（${path}）` }],
		// 注意：没有 terminate 字段
	}),
};

const writeFile = {
	name: "write_file",
	execute: async ({ path }) => ({
		content: [{ type: "text", text: `已写入 ${path}` }],
		// 注意：没有 terminate 字段
	}),
};

/**
 * 这个工具是重点。
 *
 * 它的 execute() 里有一行 if —— 这就是 terminate: true 的出生地。
 * 判断依据不是框架给的，是工具作者自己定的业务规则：
 *   "计划已经成型、但用户还没批准" → 此刻必须停下来等人，再跑下去就是自作主张。
 */
const submitPlan = {
	name: "submit_plan",
	execute: async ({ title, steps }, state) => {
		const ready = steps.length > 0;
		const approved = state.approved === true;
		const shouldStop = ready && !approved;

		log(
			`      ${C.dim}execute() 内心：ready=${ready}  approved=${approved}  →  shouldStop=${shouldStop}${C.reset}`,
		);

		const result = {
			content: [{ type: "text", text: `计划《${title}》已提交，共 ${steps.length} 步，等待批准` }],
			details: { title, steps },
		};
		if (shouldStop) result.terminate = true; // ←←← 就是这一行
		return result;
	},
};

const shell = {
	name: "shell",
	execute: async ({ cmd }) => ({
		content: [{ type: "text", text: `$ ${cmd}\n(已执行)` }],
	}),
};

// ───────────────────────────────────────────────────────────────────────
// 复刻 Pi 的单轮工具执行：三个可以写入 terminate 的关卡
// ───────────────────────────────────────────────────────────────────────

async function executeOne({ call, tool, hooks, state }) {
	const entry = { call, result: null, origin: "无", judge: "" };

	// ── 关卡 1：beforeToolCall 钩子（agent-loop.ts:738-765）──────────────
	if (hooks.beforeToolCall) {
		const r = await hooks.beforeToolCall({ call, state });
		if (r?.block) {
			const result = { content: [{ type: "text", text: r.reason }], isError: true };
			entry.origin = "beforeToolCall";
			entry.judge = r.judge ?? "";
			if (r.terminate === true) {
				result.terminate = true; // ← L757-758
				entry.origin = "beforeToolCall + block + terminate";
			}
			entry.result = result;
			return entry;
		}
		log(`      ${C.dim}beforeToolCall：放行${C.reset}`);
	} else {
		log(`      ${C.dim}beforeToolCall：未配置${C.reset}`);
	}

	// ── 关卡 2：工具自己的 execute()（L592 / L668 取 finalized.result.terminate）──
	let result = await tool.execute(call.args, state);
	if (result.terminate === true) entry.origin = "tool.execute 的 return";

	// ── 关卡 3：afterToolCall 钩子，可覆盖（L902）──────────────────────
	if (hooks.afterToolCall) {
		const r = await hooks.afterToolCall({ call, result, state });
		if (r && r.terminate !== undefined) {
			result = { ...result, terminate: r.terminate }; // ← L902 的 ?? 语义
			entry.origin = "afterToolCall 改写";
			entry.judge = r.judge ?? "";
			log(`      ${C.dim}afterToolCall：把 terminate 改写为 ${r.terminate}${C.reset}`);
		} else {
			log(`      ${C.dim}afterToolCall：不改写${C.reset}`);
		}
	}

	entry.result = result;
	return entry;
}

// ───────────────────────────────────────────────────────────────────────
// 主循环
// ───────────────────────────────────────────────────────────────────────

async function run({ label, note, userText, tools, hooks = {}, script, state = {} }) {
	log(`\n${C.bold}${C.cyan}━━━ ${label} ━━━${C.reset}`);
	log(`${C.dim}用户：${userText}${C.reset}`);
	if (note) log(`${C.dim}说明：${note}${C.reset}`);

	const messages = [{ role: "user", content: userText }];
	let reqNo = 0;
	let visible;

	while (true) {
		reqNo++;
		state.round = reqNo;
		const assistant = script(reqNo, messages, state);
		messages.push(assistant);
		const calls = assistant.content.filter((c) => c.type === "toolCall");

		if (calls.length === 0) {
			const text = assistant.content.filter((c) => c.type === "text").map((c) => c.text).join("");
			log(`\n${C.yellow}[请求 ${reqNo}]${C.reset} 模型 → ${C.dim}纯文本${C.reset}`);
			log(`      └─ 无工具调用 → hasMoreToolCalls = false → 自然收敛`);
			visible = { kind: "assistant 文本", text };
			break;
		}

		log(`\n${C.yellow}[请求 ${reqNo}]${C.reset} 模型 → ${calls.map((c) => `${c.name}(${shrink(c.args)})`).join("  +  ")}`);

		const finals = [];
		for (const call of calls) {
			log(`\n      ${C.bold}${call.name}${C.reset}`);
			const tool = tools.find((t) => t.name === call.name);
			const entry = await executeOne({ call, tool, hooks, state });
			finals.push(entry);

			const t = entry.result.terminate;
			const tStr =
				t === undefined ? `${C.dim}undefined${C.reset}` : t ? `${C.green}true${C.reset}` : `${C.red}false${C.reset}`;
			log(`      结果 terminate = ${tStr}   ${C.dim}（来源：${entry.origin}）${C.reset}`);
			if (entry.judge) log(`      ${C.dim}判断依据：${entry.judge}${C.reset}`);
		}

		// ── 批级聚合：agent-loop.ts:701 ────────────────────────────────
		const batch = finals.length > 0 && finals.every((f) => f.result.terminate === true);
		const vals = finals.map((f) => (f.result.terminate === true ? "true" : f.result.terminate === false ? "false" : "undef"));
		log(
			`\n      ${C.magenta}every([${vals.join(", ")}]) = ${batch}${C.reset}` +
				`   ${C.dim}← 全员同意才停，一票否决就继续${C.reset}`,
		);

		for (const e of finals) {
			messages.push({ role: "toolResult", toolName: e.call.name, content: e.result.content });
		}

		const hasMore = !batch;
		log(`      hasMoreToolCalls = !${batch} = ${hasMore}`);
		if (!hasMore) {
			visible = { kind: "工具结果", text: finals.map((f) => f.result.content[0].text).join(" / ") };
			log(`      ${C.green}→ 不再发请求，循环在这里结束${C.reset}`);
			break;
		}
	}

	log(`\n      ${C.bold}实际模型请求次数：${reqNo}${C.reset}`);
	log(`      ${C.bold}用户最后看到的是：${visible.kind}${C.reset} → 「${visible.text}」`);
	return { reqNo, visible, terminated: visible.kind === "工具结果" };
}

// ───────────────────────────────────────────────────────────────────────
// 场景
// ───────────────────────────────────────────────────────────────────────

const s1 = (n) =>
	n === 1
		? { role: "assistant", content: [{ type: "toolCall", name: "read_file", args: { path: "README.md" } }] }
		: n === 2
			? { role: "assistant", content: [{ type: "toolCall", name: "write_file", args: { path: "NOTES.md" } }] }
			: { role: "assistant", content: [{ type: "text", text: "README 看完了，笔记也写好了。" }] };

const s2 = (n) =>
	n === 1
		? {
				role: "assistant",
				content: [
					{
						type: "toolCall",
						name: "submit_plan",
						args: { title: "重构登录模块", steps: ["抽接口", "换实现", "补测试"] },
					},
				],
			}
		: { role: "assistant", content: [{ type: "text", text: "计划已提交，我这就开始改代码！" }] };

const s3 = (n) =>
	n <= 4
		? { role: "assistant", content: [{ type: "toolCall", name: "read_file", args: { path: `src/f${n}.ts` } }] }
		: { role: "assistant", content: [{ type: "text", text: "看完了。" }] };

const s4 = (n) =>
	n === 1
		? { role: "assistant", content: [{ type: "toolCall", name: "shell", args: { cmd: "rm -rf ./src" } }] }
		: { role: "assistant", content: [{ type: "text", text: "好的，已清理。" }] };

const s5 = (n) =>
	n === 1
		? {
				role: "assistant",
				content: [
					{ type: "toolCall", name: "read_file", args: { path: "src/app.ts" } },
					{ type: "toolCall", name: "submit_plan", args: { title: "修 bug", steps: ["复现", "修复"] } },
				],
			}
		: { role: "assistant", content: [{ type: "text", text: "代码看过了，计划也提交了，等您批准。" }] };

const results = [];

results.push(
	await run({
		label: "场景 1 · 谁都没写 terminate（最常见的 95%）",
		note: "循环的出口不是 terminate，而是模型自己不再调用工具。",
		userText: "看下 README，然后把要点记到 NOTES.md",
		tools: [readFile, writeFile],
		script: s1,
	}),
);

results.push(
	await run({
		label: "场景 2 · 工具自己 return true（来源：execute）",
		note: "submit_plan 认为：计划成型但没批准 → 必须停下等人。这条规则写在工具自己的代码里。",
		userText: "帮我定个重构登录模块的计划",
		tools: [submitPlan],
		script: s2,
		state: { approved: false },
	}),
);

results.push(
	await run({
		label: "场景 3 · 钩子强制熔断（来源：afterToolCall）",
		note: "工具本身什么都不写，由外部钩子规定「最多跑 3 轮」。业务代码一行不改。",
		userText: "把 src 下所有文件都读一遍",
		tools: [readFile],
		hooks: {
			afterToolCall: ({ state }) =>
				state.round >= 3
					? { terminate: true, judge: `round=${state.round} >= 预算 3 → 熔断` }
					: undefined,
		},
		script: s3,
	}),
);

results.push(
	await run({
		label: "场景 4 · 拦截 + 终止（来源：beforeToolCall）",
		note: "工具根本没被执行。block 让这次调用失败，terminate 让整批立刻收摊。",
		userText: "把 src 目录清掉",
		tools: [shell],
		hooks: {
			beforeToolCall: ({ call }) =>
				/rm\s+-rf/.test(call.args.cmd)
					? { block: true, terminate: true, reason: "拒绝执行：递归删除", judge: "命令命中 rm -rf 黑名单" }
					: undefined,
		},
		script: s4,
	}),
);

results.push(
	await run({
		label: "场景 5 · 混合批：一票否决（来源：every 聚合）",
		note: "submit_plan 打了 true，但同批的 read_file 没打 → every() 为 false → 循环继续。",
		userText: "看下 app.ts，顺便给个修复计划",
		tools: [readFile, submitPlan],
		script: s5,
		state: { approved: false },
	}),
);

// ───────────────────────────────────────────────────────────────────────
// 汇总
// ───────────────────────────────────────────────────────────────────────

log(`\n${C.bold}${C.cyan}━━━ 汇总 ━━━${C.reset}`);
log(`${C.dim}场景                                    true 来自           请求次数  最后给用户的是${C.reset}`);
const rows = [
	["1 无人写 terminate", "—", "助手总结"],
	["2 工具 return", "execute 的 return", "计划卡片"],
	["3 钩子熔断", "afterToolCall", "工具结果"],
	["4 拦截危险命令", "beforeToolCall", "错误结果"],
	["5 混合批一票否决", "every 不通过", "助手总结"],
];
results.forEach((r, i) => {
	log(
		`${rows[i][0].padEnd(36)} ${rows[i][1].padEnd(20)} ${String(r.reqNo).padEnd(10)} ${rows[i][2]}`,
	);
});

log(`\n${C.dim}结论：terminate 从来不是框架「算」出来的。`);
log(`      它是某个人在三个关卡之一，用一个 if 写进返回对象的一个布尔字段。`);
log(`      框架只做两件事：every() 聚合，以及 !true 之后不再发请求。${C.reset}`);
