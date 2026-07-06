<?php

/**
 * Class AiChatController
 *
 * In-app AI chat backed by the locked-down local pi sidecar.
 */
class AiChatController extends BaseController {
    const INDEX_LAYOUT = 'layout';

    public function __construct()
    {
        parent::__construct(self::INDEX_LAYOUT);
    }

    /**
     * Handles URL: /ai-chat
     */
    public function view_chat($request)
    {
        if (!AiChatConfigModule::isEnabled()) {
            FlashMessage::setMsg('error', __('app.ai_chat_disabled'));
            return redirect('/');
        }

        return parent::view(['content', 'ai_chat'], [
            'user' => UserModel::getAuthUser(),
            '_ai_chat' => true
        ]);
    }

    /**
     * Handles URL: /ai-chat/sessions
     */
    public function sessions($request)
    {
        try {
            $user = UserModel::getAuthUser();
            $rows = AiChatSessionModel::getForUser($user->get('id'));
            $sessions = [];
            foreach ($rows as $row) {
                $sessions[] = $this->serializeSession($row);
            }

            return json(['code' => 200, 'sessions' => $sessions]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Handles URL: /ai-chat/messages
     */
    public function messages($request)
    {
        try {
            $user = UserModel::getAuthUser();
            $sessionId = safe_int($request->params()->query('session_id', 0));
            $this->requireSession($sessionId, $user->get('id'));

            return json([
                'code' => 200,
                'messages' => $this->serializeMessages($sessionId),
                'actions' => $this->serializeActions($sessionId)
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Handles URL: /ai-chat/send
     */
    public function send_message($request)
    {
        try {
            if (!AiChatConfigModule::isEnabled()) {
                throw new \Exception(__('app.ai_chat_disabled'));
            }

            $user = UserModel::getAuthUser();
            $message = trim($request->params()->query('message', ''));
            if ($message === '') {
                throw new \Exception(__('app.ai_chat_empty_message'));
            }

            $sessionId = safe_int($request->params()->query('session_id', 0));
            if ($sessionId <= 0) {
                $sessionId = AiChatSessionModel::createSession($user->get('id'), $this->titleFromMessage($message));
            } else {
                $this->requireSession($sessionId, $user->get('id'));
            }

            $history = AiChatMsgModel::getHistoryForSidecar($sessionId, 20);
            AiChatMsgModel::addMessage($sessionId, 'user', $message);

            $sidecar = AiChatSidecarModule::chat($sessionId, $message, $history);
            $reply = trim($sidecar['reply'] ?? '');
            if ($reply === '') {
                $reply = __('app.ai_chat_empty_reply');
            }

            $assistantMsgId = AiChatMsgModel::addMessage($sessionId, 'assistant', $reply, [
                'model' => $sidecar['model'] ?? AiChatConfigModule::value('model')
            ]);

            $createdActions = [];
            foreach (($sidecar['pending_actions'] ?? []) as $action) {
                if (!is_array($action)) continue;
                $actionId = AiChatActionModel::createPending($sessionId, $assistantMsgId, $user->get('id'), $action);
                $row = AiChatActionModel::raw('SELECT * FROM `@THIS` WHERE id = ?', [$actionId])->first();
                if ($row) {
                    $createdActions[] = $this->serializeAction($row);
                }
            }

            return json([
                'code' => 200,
                'session_id' => $sessionId,
                'reply' => $this->serializeMessageById($assistantMsgId),
                'pending_actions' => $createdActions,
                'sessions' => $this->serializeSessionsForUser($user->get('id'))
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Handles URL: /ai-chat/action/approve
     */
    public function approve_action($request)
    {
        try {
            $user = UserModel::getAuthUser();
            $actionId = safe_int($request->params()->query('action_id', 0));
            $action = AiChatActionModel::getPendingForUser($actionId, $user->get('id'));
            if (!$action) {
                throw new \Exception(__('app.ai_chat_action_not_found'));
            }

            $args = json_decode($action->get('args_json'), true);
            if (!is_array($args)) $args = [];

            try {
                $result = AiChatSidecarModule::executeAction($action->get('tool_name'), $args);
                AiChatActionModel::setStatus($actionId, AiChatActionModel::STATUS_APPROVED, $result['result'] ?? $result);
                AiChatMsgModel::addMessage($action->get('session_id'), 'assistant', __('app.ai_chat_action_approved_message'));
            } catch (\Exception $e) {
                AiChatActionModel::setStatus($actionId, AiChatActionModel::STATUS_FAILED, ['error' => $e->getMessage()]);
                throw $e;
            }

            return json([
                'code' => 200,
                'messages' => $this->serializeMessages($action->get('session_id')),
                'actions' => $this->serializeActions($action->get('session_id'))
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Handles URL: /ai-chat/action/reject
     */
    public function reject_action($request)
    {
        try {
            $user = UserModel::getAuthUser();
            $actionId = safe_int($request->params()->query('action_id', 0));
            $action = AiChatActionModel::getPendingForUser($actionId, $user->get('id'));
            if (!$action) {
                throw new \Exception(__('app.ai_chat_action_not_found'));
            }

            AiChatActionModel::setStatus($actionId, AiChatActionModel::STATUS_REJECTED);
            AiChatMsgModel::addMessage($action->get('session_id'), 'assistant', __('app.ai_chat_action_rejected_message'));

            return json([
                'code' => 200,
                'messages' => $this->serializeMessages($action->get('session_id')),
                'actions' => $this->serializeActions($action->get('session_id'))
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    private function requireSession($sessionId, $userId)
    {
        $session = AiChatSessionModel::getForUserById($sessionId, $userId);
        if (!$session) {
            throw new \Exception(__('app.ai_chat_session_not_found'));
        }
        return $session;
    }

    private function serializeSessionsForUser($userId)
    {
        $sessions = [];
        foreach (AiChatSessionModel::getForUser($userId) as $row) {
            $sessions[] = $this->serializeSession($row);
        }
        return $sessions;
    }

    private function serializeSession($row)
    {
        return [
            'id' => $row->get('id'),
            'title' => $row->get('title'),
            'created_at' => $row->get('created_at'),
            'updated_at' => $row->get('updated_at'),
        ];
    }

    private function serializeMessageById($id)
    {
        $row = AiChatMsgModel::raw('SELECT * FROM `@THIS` WHERE id = ?', [$id])->first();
        return $row ? $this->serializeMessage($row) : null;
    }

    private function serializeMessages($sessionId)
    {
        $messages = [];
        foreach (AiChatMsgModel::getForSession($sessionId) as $row) {
            $messages[] = $this->serializeMessage($row);
        }
        return $messages;
    }

    private function serializeMessage($row)
    {
        return [
            'id' => $row->get('id'),
            'session_id' => $row->get('session_id'),
            'role' => $row->get('role'),
            'content' => UtilsModule::purify($row->get('content')),
            'created_at' => $row->get('created_at'),
            'diffForHumans' => (new Carbon($row->get('created_at')))->diffForHumans(),
        ];
    }

    private function serializeActions($sessionId)
    {
        $actions = [];
        foreach (AiChatActionModel::getForSession($sessionId) as $row) {
            $actions[] = $this->serializeAction($row);
        }
        return $actions;
    }

    private function serializeAction($row)
    {
        $preview = json_decode($row->get('preview_json'), true);
        $args = json_decode($row->get('args_json'), true);
        $result = json_decode($row->get('result_json'), true);
        return [
            'id' => $row->get('id'),
            'session_id' => $row->get('session_id'),
            'message_id' => $row->get('message_id'),
            'tool_name' => $row->get('tool_name'),
            'args' => is_array($args) ? $args : [],
            'preview' => is_array($preview) ? $preview : [],
            'status' => $row->get('status'),
            'result' => is_array($result) ? $result : null,
            'created_at' => $row->get('created_at'),
        ];
    }

    private function titleFromMessage($message)
    {
        $title = trim(preg_replace('/\s+/', ' ', $message));
        if (strlen($title) > 64) {
            $title = substr($title, 0, 61) . '...';
        }
        return $title ?: __('app.ai_chat_new_session');
    }
}
