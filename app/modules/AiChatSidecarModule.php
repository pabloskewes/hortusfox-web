<?php

/**
 * Class AiChatSidecarModule
 *
 * HTTP client for the local pi SDK sidecar.
 */
class AiChatSidecarModule {
    /**
     * @param int $sessionId
     * @param string $message
     * @param array $history
     * @return array
     * @throws \Exception
     */
    public static function chat($sessionId, $message, $history)
    {
        return self::post('/chat', [
            'session_id' => $sessionId,
            'user_message' => $message,
            'messages' => $history,
            'model' => AiChatConfigModule::value('model', 'opencode-go/deepseek-v4-pro'),
            'thinking_level' => AiChatConfigModule::value('thinking_level', 'off'),
            'allowed_tools' => AiChatConfigModule::value('allowed_tools', []),
            'mutation_tools' => AiChatConfigModule::value('mutation_tools', []),
        ]);
    }

    /**
     * @param string $toolName
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public static function executeAction($toolName, $args)
    {
        return self::post('/execute-action', [
            'tool_name' => $toolName,
            'args' => $args
        ]);
    }

    /**
     * @param string $path
     * @param array $payload
     * @return array
     * @throws \Exception
     */
    private static function post($path, $payload)
    {
        $baseUrl = rtrim(AiChatConfigModule::value('sidecar_url', 'http://127.0.0.1:8787'), '/');
        $token = AiChatConfigModule::sidecarToken();
        if (!$token) {
            throw new \Exception('AI chat sidecar token is not configured');
        }

        $ch = curl_init($baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('AI chat sidecar request failed: ' . $error);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \Exception('AI chat sidecar returned invalid JSON');
        }

        if (($status < 200) || ($status >= 300) || (($decoded['code'] ?? 200) !== 200)) {
            throw new \Exception($decoded['msg'] ?? ('AI chat sidecar returned HTTP ' . $status));
        }

        return $decoded;
    }
}
