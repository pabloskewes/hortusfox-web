<?php

/**
 * Class AiChatConfigModule
 *
 * Loads developer-controlled AI chat configuration from app/config/ai_chat.json.
 */
class AiChatConfigModule {
    private static $config = null;

    /**
     * @return array
     */
    public static function get()
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $path = __DIR__ . '/../config/ai_chat.json';
        $example = __DIR__ . '/../config/ai_chat.json.example';
        $source = file_exists($path) ? $path : $example;

        $raw = @file_get_contents($source);
        if ($raw === false) {
            self::$config = [];
            return self::$config;
        }

        $decoded = json_decode($raw, true);
        self::$config = is_array($decoded) ? $decoded : [];
        return self::$config;
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        $config = self::get();
        return (bool)($config['enabled'] ?? false);
    }

    /**
     * @param string $key
     * @param mixed $fallback
     * @return mixed
     */
    public static function value($key, $fallback = null)
    {
        $config = self::get();
        return $config[$key] ?? $fallback;
    }

    /**
     * @return string
     */
    public static function sidecarToken()
    {
        $envName = self::value('sidecar_token_env', 'AI_CHAT_SIDECAR_TOKEN');
        $token = env($envName, '');
        if ($token) {
            return $token;
        }

        $envPath = __DIR__ . '/../../ai-chat/.env';
        if (!file_exists($envPath)) {
            return '';
        }

        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (($line === '') || (strpos($line, '#') === 0) || (strpos($line, '=') === false)) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            if (trim($key) === $envName) {
                return trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        return '';
    }
}
