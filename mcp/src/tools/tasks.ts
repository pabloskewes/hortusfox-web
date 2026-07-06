import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { apiGet, apiPost } from "../client.js";
import { jsonResult, errorResult } from "./_util.js";

const RECURRING_SCOPES = ["hours", "days", "weeks", "months", "years"] as const;

export function registerTaskTools(server: McpServer): void {
    server.tool(
        "list_tasks",
        "List tasks in the HortusFox workspace, optionally filtered by completion state.",
        {
            done: z.boolean()
                .optional()
                .describe("If true, return completed tasks. If false (default), return open tasks."),
            limit: z.number().int().positive()
                .optional()
                .describe("Maximum number of tasks to return. Defaults to 100."),
        },
        async ({ done, limit }) => {
            try {
                const result = await apiGet("tasks/fetch", {
                    done: done ?? false,
                    limit: limit ?? 100,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "add_task",
        `Create a new task. Both due_date and recurring_time must be provided together to make the task recurring; if either is missing, the task is one-off.

due_date format: "YYYY-MM-DD HH:MM:SS" (the server's local time, no timezone).

recurring_time is a number in the chosen recurring_scope. For example, recurring_time=2 with recurring_scope="weeks" means the task repeats every 2 weeks.`,
        {
            title: z.string().min(1)
                .describe("Short title of the task."),
            description: z.string()
                .optional()
                .describe("Longer description of the task."),
            due_date: z.string()
                .optional()
                .describe('When the task is due, in "YYYY-MM-DD HH:MM:SS" format.'),
            recurring_time: z.number().int().positive()
                .optional()
                .describe("Recurrence interval, in units of recurring_scope. Omit to make the task one-off."),
            recurring_scope: z.enum(RECURRING_SCOPES)
                .optional()
                .describe("Unit for recurring_time. Defaults to \"hours\"."),
            plant_id: z.number().int().positive()
                .optional()
                .describe("Optional plant ID to link the task to."),
        },
        async ({ title, description, due_date, recurring_time, recurring_scope, plant_id }) => {
            try {
                const result = await apiPost("tasks/add", {
                    title,
                    description: description ?? "",
                    due_date,
                    recurring_time: recurring_time ?? 0,
                    recurring_scope: recurring_scope ?? "hours",
                    plant: plant_id ?? 0,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );
}
