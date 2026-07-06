<?php

/**
 * Class AiChatSessionModel
 *
 * Stores per-user AI chat sessions.
 */
class AiChatSessionModel extends \Asatru\Database\Model {
    /**
     * @param int $userId
     * @param string|null $title
     * @return int
     */
    public static function createSession($userId, $title = null)
    {
        $title = $title ?: __('app.ai_chat_new_session');
        static::raw('INSERT INTO `@THIS` (user_id, title) VALUES(?, ?)', [$userId, $title]);
        return static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first()->get('id');
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public static function getForUser($userId)
    {
        return static::raw('SELECT * FROM `@THIS` WHERE user_id = ? ORDER BY updated_at DESC, id DESC', [$userId]);
    }

    /**
     * @param int $id
     * @param int $userId
     * @return mixed
     */
    public static function getForUserById($id, $userId)
    {
        return static::raw('SELECT * FROM `@THIS` WHERE id = ? AND user_id = ? LIMIT 1', [$id, $userId])->first();
    }

    /**
     * @param int $id
     * @return void
     */
    public static function touch($id)
    {
        static::raw('UPDATE `@THIS` SET updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$id]);
    }

    /**
     * @param int $id
     * @param string $title
     * @return void
     */
    public static function updateTitle($id, $title)
    {
        static::raw('UPDATE `@THIS` SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$title, $id]);
    }
}
