<style>
    .ai-chat-shell { min-height: 68vh; }
    .ai-chat-sessions { border-right: 1px solid rgba(127, 127, 127, 0.2); }
    .ai-chat-session { display: block; padding: 0.75rem; border-radius: 8px; color: inherit; }
    .ai-chat-session:hover, .ai-chat-session.is-active { background: rgba(127, 127, 127, 0.12); }
    .ai-chat-thread { min-height: 48vh; max-height: 62vh; overflow-y: auto; padding: 1rem; border-radius: 10px; background: rgba(127, 127, 127, 0.08); }
    .ai-chat-bubble { max-width: 86%; margin-bottom: 1rem; padding: 0.85rem 1rem; border-radius: 14px; white-space: pre-wrap; }
    .ai-chat-bubble-user { margin-left: auto; background: #48c78e; color: #082016; }
    .ai-chat-bubble-assistant { margin-right: auto; background: rgba(255, 255, 255, 0.9); color: #1f2933; }
    .ai-chat-action { margin: 0.75rem 0 1rem 0; border: 1px solid #ffdd57; border-radius: 10px; padding: 1rem; background: rgba(255, 221, 87, 0.12); }
    .ai-chat-action-fields { margin-top: 0.75rem; }
    .ai-chat-action-field { display: flex; gap: 0.5rem; margin-bottom: 0.25rem; }
    .ai-chat-action-field strong { min-width: 9rem; }
    .ai-chat-empty { padding: 2rem; text-align: center; opacity: 0.75; }
    @media (max-width: 768px) {
        .ai-chat-sessions { border-right: 0; border-bottom: 1px solid rgba(127, 127, 127, 0.2); margin-bottom: 1rem; }
        .ai-chat-bubble { max-width: 96%; }
        .ai-chat-thread { max-height: 55vh; }
    }
</style>

<div class="columns">
    <div class="column is-1"></div>

    <div class="column is-10">
        <h1>{{ __('app.ai_chat') }}</h1>
        <h2 class="smaller-headline">{{ __('app.ai_chat_hint') }}</h2>

        @include('flashmsg.php')

        <div class="columns ai-chat-shell" id="ai-chat-app">
            <div class="column is-3 ai-chat-sessions">
                <div class="margin-bottom">
                    <button class="button is-success is-fullwidth" id="ai-chat-new-session">
                        <i class="fas fa-plus"></i>&nbsp;{{ __('app.ai_chat_new_session') }}
                    </button>
                </div>

                <div id="ai-chat-session-list"></div>
            </div>

            <div class="column is-9">
                <div class="ai-chat-thread" id="ai-chat-thread">
                    <div class="ai-chat-empty">{{ __('app.ai_chat_empty_state') }}</div>
                </div>

                <form id="ai-chat-form" class="margin-vertical">
                    <div class="field">
                        <div class="control">
                            <textarea class="textarea is-input-dark" id="ai-chat-input" rows="3" placeholder="{{ __('app.ai_chat_placeholder') }}"></textarea>
                        </div>
                    </div>

                    <div class="field is-grouped is-justify-content-flex-end">
                        <div class="control">
                            <button class="button is-success" id="ai-chat-send" type="submit">{{ __('app.send') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="column is-1"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = {
        emptyState: '{{ __('app.ai_chat_empty_state') }}',
        pendingApproval: '{{ __('app.ai_chat_pending_approval') }}',
        approve: '{{ __('app.ai_chat_approve') }}',
        reject: '{{ __('app.ai_chat_reject') }}',
        approved: '{{ __('app.ai_chat_approved') }}',
        rejected: '{{ __('app.ai_chat_rejected') }}',
        failed: '{{ __('app.ai_chat_failed') }}',
        loading: '{{ __('app.loading_please_wait') }}'
    };

    let sessions = [];
    let currentSessionId = null;
    let messages = [];
    let actions = [];

    const sessionList = document.getElementById('ai-chat-session-list');
    const thread = document.getElementById('ai-chat-thread');
    const input = document.getElementById('ai-chat-input');
    const sendButton = document.getElementById('ai-chat-send');

    document.getElementById('ai-chat-new-session').addEventListener('click', function() {
        currentSessionId = null;
        messages = [];
        actions = [];
        input.value = '';
        renderSessions();
        renderThread();
        input.focus();
    });

    document.getElementById('ai-chat-form').addEventListener('submit', function(event) {
        event.preventDefault();
        sendMessage();
    });

    input.addEventListener('keydown', function(event) {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    });

    loadSessions();

    function loadSessions() {
        window.axios.get(window.location.origin + '/ai-chat/sessions').then(function(response) {
            if (response.data.code !== 200) throw new Error(response.data.msg);
            sessions = response.data.sessions;
            if (!currentSessionId && sessions.length > 0) {
                currentSessionId = sessions[0].id;
                loadMessages(currentSessionId);
            }
            renderSessions();
            renderThread();
        }).catch(showError);
    }

    function loadMessages(sessionId) {
        window.axios.get(window.location.origin + '/ai-chat/messages?session_id=' + encodeURIComponent(sessionId)).then(function(response) {
            if (response.data.code !== 200) throw new Error(response.data.msg);
            messages = response.data.messages;
            actions = response.data.actions;
            renderThread();
        }).catch(showError);
    }

    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        sendButton.disabled = true;
        sendButton.innerText = labels.loading;
        messages.push({ role: 'user', content: escapeHtml(text), diffForHumans: '' });
        renderThread();
        input.value = '';

        const body = new URLSearchParams();
        body.set('message', text);
        if (currentSessionId) body.set('session_id', currentSessionId);

        window.axios.post(window.location.origin + '/ai-chat/send', body).then(function(response) {
            if (response.data.code !== 200) throw new Error(response.data.msg);
            currentSessionId = response.data.session_id;
            sessions = response.data.sessions;
            loadMessages(currentSessionId);
            renderSessions();
        }).catch(showError).finally(function() {
            sendButton.disabled = false;
            sendButton.innerText = '{{ __('app.send') }}';
            input.focus();
        });
    }

    function updateAction(actionId, path) {
        const body = new URLSearchParams();
        body.set('action_id', actionId);
        window.axios.post(window.location.origin + path, body).then(function(response) {
            if (response.data.code !== 200) throw new Error(response.data.msg);
            messages = response.data.messages;
            actions = response.data.actions;
            renderThread();
        }).catch(showError);
    }

    function renderSessions() {
        sessionList.innerHTML = '';
        sessions.forEach(function(session) {
            const link = document.createElement('a');
            link.href = 'javascript:void(0);';
            link.className = 'ai-chat-session' + ((session.id == currentSessionId) ? ' is-active' : '');
            link.innerHTML = '<strong>' + escapeHtml(session.title) + '</strong><br/><small>' + escapeHtml(session.updated_at || '') + '</small>';
            link.addEventListener('click', function() {
                currentSessionId = session.id;
                renderSessions();
                loadMessages(session.id);
            });
            sessionList.appendChild(link);
        });
    }

    function renderThread() {
        thread.innerHTML = '';
        if (messages.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ai-chat-empty';
            empty.innerText = labels.emptyState;
            thread.appendChild(empty);
            return;
        }

        messages.forEach(function(message) {
            const bubble = document.createElement('div');
            bubble.className = 'ai-chat-bubble ' + ((message.role === 'user') ? 'ai-chat-bubble-user' : 'ai-chat-bubble-assistant');
            bubble.innerHTML = message.content;
            thread.appendChild(bubble);

            actions.filter(function(action) { return action.message_id == message.id; }).forEach(function(action) {
                thread.appendChild(renderAction(action));
            });
        });
        thread.scrollTop = thread.scrollHeight;
    }

    function renderAction(action) {
        const card = document.createElement('div');
        card.className = 'ai-chat-action';
        const preview = action.preview || {};
        let html = '<strong>' + labels.pendingApproval + '</strong><br/>';
        html += '<div>' + escapeHtml(preview.summary || action.tool_name) + '</div>';
        html += '<div class="ai-chat-action-fields">';
        (preview.fields || []).forEach(function(field) {
            html += '<div class="ai-chat-action-field"><strong>' + escapeHtml(field.label) + '</strong><span>' + escapeHtml(field.value) + '</span></div>';
        });
        html += '</div><div class="buttons margin-top">';
        if (action.status === 'pending') {
            html += '<button class="button is-success is-small" data-ai-approve="' + action.id + '">' + labels.approve + '</button>';
            html += '<button class="button is-danger is-small" data-ai-reject="' + action.id + '">' + labels.reject + '</button>';
        } else {
            html += '<span class="tag ' + statusClass(action.status) + '">' + statusLabel(action.status) + '</span>';
        }
        html += '</div>';
        card.innerHTML = html;

        const approve = card.querySelector('[data-ai-approve]');
        if (approve) approve.addEventListener('click', function() { updateAction(action.id, '/ai-chat/action/approve'); });
        const reject = card.querySelector('[data-ai-reject]');
        if (reject) reject.addEventListener('click', function() { updateAction(action.id, '/ai-chat/action/reject'); });
        return card;
    }

    function statusClass(status) {
        if (status === 'approved') return 'is-success';
        if (status === 'rejected') return 'is-danger';
        if (status === 'failed') return 'is-warning';
        return 'is-light';
    }

    function statusLabel(status) {
        if (status === 'approved') return labels.approved;
        if (status === 'rejected') return labels.rejected;
        if (status === 'failed') return labels.failed;
        return status;
    }

    function showError(error) {
        alert(error.message || String(error));
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, function(chr) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[chr];
        });
    }
});
</script>
