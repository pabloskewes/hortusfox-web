import type { CallToolResult } from "@modelcontextprotocol/sdk/types.js";
import { formatErrorForLlm } from "../errors.js";

export function jsonResult(data: unknown): CallToolResult {
    return {
        content: [{ type: "text", text: JSON.stringify(data, null, 2) }],
    };
}

export function errorResult(err: unknown): CallToolResult {
    return {
        isError: true,
        content: [{ type: "text", text: formatErrorForLlm(err) }],
    };
}
