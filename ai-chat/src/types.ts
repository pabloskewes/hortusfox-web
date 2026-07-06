export type ChatMessage = {
    role: "user" | "assistant";
    content: string;
};

export type PendingAction = {
    client_action_id: string;
    tool_name: string;
    args: Record<string, unknown>;
    preview: {
        title: string;
        summary: string;
        fields: Array<{ label: string; value: string }>;
    };
};

export type ChatRequest = {
    session_id: number | string;
    user_message: string;
    messages?: ChatMessage[];
    model?: string;
    thinking_level?: string;
    allowed_tools?: string[];
    mutation_tools?: string[];
};

export type ChatResponse = {
    reply: string;
    pending_actions: PendingAction[];
    model: string;
};

export type ExecuteActionRequest = {
    tool_name: string;
    args: Record<string, unknown>;
};

export type ExecuteActionResponse = {
    result: unknown;
};
