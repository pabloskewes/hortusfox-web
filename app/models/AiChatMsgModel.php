<?php

/**
 * Class AiChatMsgModel
 *
 * Stores messages for AI chat sessions.
 */
class AiChatMsgModel extends \Asatru\Database\Model {
    /**
     * @param int $sessionId
     * @param string $role
     * @param string $content
     * @param array|null $metadata
     * @return int
     */
    public static function addMessage($sessionId, $role, $content, $metadata = null)
    {
        static::raw('INSERT INTO `@THIS` (session_id, role, content, metadata_json) VALUES(?, ?, ?, ?)', [
            $sessionId,
            $role,
            $content,
            ($metadata !== null) ? json_encode($metadata) : null
        ]);

        AiChatSessionModel::touch($sessionId);

        return static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first()->get('id');
    }

    /**
     * @param int $sessionId
     * @param int $limit
     * @return mixed
     */
    public static function getForSession($sessionId, $limit = 100)
    {
        return static::raw('SELECT * FROM `@THIS` WHERE session_id = ? ORDER BY id ASC LIMIT ' . safe_int($limit, 100), [$sessionId]);
    }

    /**
     * @param int $sessionId
     * @param int $limit
     * @return array
     */
    public static function getHistoryForSidecar($sessionId, $limit = 20)
    {
        $rows = static::raw('SELECT * FROM `@THIS` WHERE session_id = ? ORDER BY id DESC LIMIT ' . safe_int($limit, 20), [$sessionId]);
        $result = [];
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $row = $rows->get($i);
            $result[] = [
                'role' => $row->get('role'),
                'content' => $row->get('content')
            ];
        }
        return $result;
    }
}
