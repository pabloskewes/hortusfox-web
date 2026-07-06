import http from "node:http";
import { config } from "./config.js";
import { runChatTurn } from "./pi-runtime.js";
import { executeApprovedAction } from "./tools.js";
import type { ChatRequest, ExecuteActionRequest } from "./types.js";

const server = http.createServer(async (req, res) => {
    try {
        if (req.method === "GET" && req.url === "/health") {
            sendJson(res, 200, { code: 200, status: "ok" });
            return;
        }

        requireAuth(req);

        if (req.method === "POST" && req.url === "/chat") {
            const body = await readJson<ChatRequest>(req);
            if (!body.user_message || !body.session_id) {
                throw httpError(400, "session_id and user_message are required");
            }
            const response = await runChatTurn(body);
            sendJson(res, 200, { code: 200, ...response });
            return;
        }

        if (req.method === "POST" && req.url === "/execute-action") {
            const body = await readJson<ExecuteActionRequest>(req);
            if (!body.tool_name || !body.args) {
                throw httpError(400, "tool_name and args are required");
            }
            const result = await executeApprovedAction(body.tool_name, body.args);
            sendJson(res, 200, { code: 200, result });
            return;
        }

        throw httpError(404, "Not found");
    } catch (err) {
        const status = typeof err === "object" && err !== null && "status" in err ? Number((err as { status: unknown }).status) : 500;
        const message = err instanceof Error ? err.message : String(err);
        sendJson(res, status || 500, { code: status || 500, msg: message });
    }
});

server.listen(config.port, config.host, () => {
    process.stderr.write(`hortusfox ai-chat sidecar listening on http://${config.host}:${config.port}\n`);
});

function requireAuth(req: http.IncomingMessage): void {
    const header = req.headers.authorization ?? "";
    if (header !== `Bearer ${config.sidecarToken}`) {
        throw httpError(401, "Unauthorized");
    }
}

function readJson<T>(req: http.IncomingMessage): Promise<T> {
    return new Promise((resolve, reject) => {
        let raw = "";
        req.on("data", (chunk) => {
            raw += chunk;
            if (raw.length > 1024 * 1024) {
                req.destroy();
                reject(httpError(413, "Request body too large"));
            }
        });
        req.on("end", () => {
            try {
                resolve(JSON.parse(raw || "{}") as T);
            } catch {
                reject(httpError(400, "Invalid JSON body"));
            }
        });
        req.on("error", reject);
    });
}

function sendJson(res: http.ServerResponse, status: number, payload: unknown): void {
    res.writeHead(status, { "Content-Type": "application/json" });
    res.end(JSON.stringify(payload));
}

function httpError(status: number, message: string): Error & { status: number } {
    const err = new Error(message) as Error & { status: number };
    err.status = status;
    return err;
}
