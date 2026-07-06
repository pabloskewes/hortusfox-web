<?php

/**
 * Class AiChatActionModel
 *
 * Stores pending and executed AI tool actions that mutate HortusFox state.
 */
class AiChatActionModel extends \Asatru\Database\Model {
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';

    /**
     * @param int $sessionId
     * @param int $messageId
     * @param int $userId
     * @param array $action
     * @return int
     */
    public static function createPending($sessionId, $messageId, $userId, $action)
    {
        static::raw('INSERT INTO `@THIS` (session_id, message_id, user_id, tool_name, args_json, preview_json, status) VALUES(?, ?, ?, ?, ?, ?, ?)', [
            $sessionId,
            $messageId,
            $userId,
            $action['tool_name'],
            json_encode($action['args'] ?? []),
            json_encode($action['preview'] ?? []),
            self::STATUS_PENDING
        ]);

        return static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first()->get('id');
    }

    /**
     * @param int $sessionId
     * @return mixed
     */
    public static function getForSession($sessionId)
    {
        return static::raw('SELECT * FROM `@THIS` WHERE session_id = ? ORDER BY id ASC', [$sessionId]);
    }

    /**
     * @param int $id
     * @param int $userId
     * @return mixed
     */
    public static function getPendingForUser($id, $userId)
    {
        return static::raw('SELECT * FROM `@THIS` WHERE id = ? AND user_id = ? AND status = ? LIMIT 1', [$id, $userId, self::STATUS_PENDING])->first();
    }

    /**
     * @param int $id
     * @param string $status
     * @param array|null $result
     * @return void
     */
    public static function setStatus($id, $status, $result = null)
    {
        static::raw('UPDATE `@THIS` SET status = ?, result_json = ?, updated_at = CURRENT_TIMESTAMP, executed_at = CURRENT_TIMESTAMP WHERE id = ?', [
            $status,
            ($result !== null) ? json_encode($result) : null,
            $id
        ]);
    }
}
