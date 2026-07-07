<script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>

<div class="columns">
    <div class="column is-1"></div>

    <div class="column is-10">
        <h1>{{ __('app.ai_chat') }}</h1>
        <h2 class="smaller-headline">{{ __('app.ai_chat_hint') }}</h2>

        @include('flashmsg.php')

        <div class="columns ai-chat-shell" id="ai-chat-app">
            <div class="column is-3 ai-chat-sidebar">
                <div class="margin-bottom">
                    <button class="button is-success is-fullwidth" id="ai-chat-new-session">
                        <span class="icon"><i class="fas fa-plus"></i></span>
                        <span>{{ __('app.ai_chat_new_session') }}</span>
                    </button>
                </div>
                <div id="ai-session-list"></div>
            </div>

            <div class="column is-9 ai-chat-main">
                <div class="ai-thread" id="ai-thread">
                    <div class="ai-empty" id="ai-empty-state">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <p>{{ __('app.ai_chat_empty_state') }}</p>
                    </div>
                </div>

                <div class="ai-input-area">
                    <form id="ai-chat-form">
                        <div class="ai-input-shell">
                            <textarea id="ai-input" rows="2" placeholder="{{ __('app.ai_chat_placeholder') }}"></textarea>
                            <button class="ai-send-btn" id="ai-send" type="submit" title="{{ __('app.send') }}">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <p class="ai-send-hint">{{ __('app.ai_chat_send_hint') }}</p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="column is-1"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const L = {
        pendingApproval: '{{ __('app.ai_chat_pending_approval') }}',
        approve:         '{{ __('app.ai_chat_approve') }}',
        reject:          '{{ __('app.ai_chat_reject') }}',
        approved:        '{{ __('app.ai_chat_approved') }}',
        rejected:        '{{ __('app.ai_chat_rejected') }}',
        failed:          '{{ __('app.ai_chat_failed') }}',
        loading:         '{{ __('app.loading_please_wait') }}',
        send:            '{{ __('app.send') }}',
        today:           '{{ __('app.ai_chat_group_today') }}',
        yesterday:       '{{ __('app.ai_chat_group_yesterday') }}',
        last7:           '{{ __('app.ai_chat_group_week') }}',
        older:           '{{ __('app.ai_chat_group_older') }}',
        enrichPrompt:    '{{ __('app.ai_chat_enrich_prompt') }}'
    };

    const md = (typeof marked !== 'undefined') ? function(text) {
        return marked.parse(text, { breaks: true, gfm: true });
    } : function(text) {
        return escHtml(text).replace(/\n/g, '<br>');
    };

    let sessions = [], currentSessionId = null, messages = [], actions = [];

    const sessionList = document.getElementById('ai-session-list');
    const thread      = document.getElementById('ai-thread');
    const emptyState  = document.getElementById('ai-empty-state');
    const input       = document.getElementById('ai-input');
    const sendBtn     = document.getElementById('ai-send');

    document.getElementById('ai-chat-new-session').addEventListener('click', function() {
        currentSessionId = null;
        messages = []; actions = [];
        renderSessions();
        renderThread();
        input.focus();
    });

    document.getElementById('ai-chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    input.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    /* Plant enrichment entry point: /ai-chat?enrich_plant={id} starts a
       fresh session and auto-sends the enrichment request */
    const enrichPlant = new URLSearchParams(window.location.search).get('enrich_plant');

    loadSessions();

    if (enrichPlant) {
        window.history.replaceState({}, '', window.location.pathname);
        input.value = L.enrichPrompt.replace(':id', enrichPlant);
        sendMessage();
    }

    /* ---- data ---- */

    function loadSessions() {
        window.axios.get(origin() + '/ai-chat/sessions').then(function(r) {
            if (r.data.code !== 200) throw new Error(r.data.msg);
            sessions = r.data.sessions;
            if (!enrichPlant && !currentSessionId && sessions.length > 0) {
                currentSessionId = sessions[0].id;
                loadMessages(currentSessionId);
            }
            renderSessions();
            if (!currentSessionId) renderThread();
        }).catch(showError);
    }

    function loadMessages(sessionId) {
        window.axios.get(origin() + '/ai-chat/messages?session_id=' + encodeURIComponent(sessionId)).then(function(r) {
            if (r.data.code !== 200) throw new Error(r.data.msg);
            messages = r.data.messages;
            actions  = r.data.actions;
            renderThread();
        }).catch(showError);
    }

    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        setBusy(true);

        const optimistic = { id: null, role: 'user', content: text, diffForHumans: '' };
        messages.push(optimistic);
        renderThread();
        input.value = '';

        const body = new URLSearchParams({ message: text });
        if (currentSessionId) body.set('session_id', currentSessionId);

        window.axios.post(origin() + '/ai-chat/send', body).then(function(r) {
            if (r.data.code !== 200) throw new Error(r.data.msg);
            currentSessionId = r.data.session_id;
            sessions = r.data.sessions;
            loadMessages(currentSessionId);
            renderSessions();
        }).catch(showError).finally(function() { setBusy(false); input.focus(); });
    }

    function updateAction(actionId, path) {
        window.axios.post(origin() + path, new URLSearchParams({ action_id: actionId })).then(function(r) {
            if (r.data.code !== 200) throw new Error(r.data.msg);
            messages = r.data.messages;
            actions  = r.data.actions;
            renderThread();
        }).catch(showError);
    }

    /* ---- render ---- */

    function renderSessions() {
        sessionList.innerHTML = '';
        const today = new Date(); today.setHours(0,0,0,0);
        const yesterday = new Date(today); yesterday.setDate(yesterday.getDate()-1);
        const week = new Date(today); week.setDate(week.getDate()-7);

        let lastGroup = null;
        sessions.forEach(function(s) {
            const d = s.updated_at ? new Date(s.updated_at.replace(' ','T')) : null;
            let group = L.older;
            if (d) {
                const dd = new Date(d); dd.setHours(0,0,0,0);
                if (dd >= today) group = L.today;
                else if (dd >= yesterday) group = L.yesterday;
                else if (dd >= week) group = L.last7;
            }
            if (group !== lastGroup) {
                const lbl = document.createElement('div');
                lbl.className = 'ai-session-group-label';
                lbl.textContent = group;
                sessionList.appendChild(lbl);
                lastGroup = group;
            }
            const el = document.createElement('div');
            el.className = 'ai-session-item' + (s.id == currentSessionId ? ' is-active' : '');
            el.textContent = s.title || 'Chat';
            el.title = s.title || '';
            el.addEventListener('click', function() {
                currentSessionId = s.id;
                renderSessions();
                loadMessages(s.id);
            });
            sessionList.appendChild(el);
        });
    }

    function renderThread() {
        thread.innerHTML = '';

        if (messages.length === 0) {
            thread.appendChild(emptyState);
            return;
        }

        messages.forEach(function(msg) {
            const isUser = msg.role === 'user';

            const row = document.createElement('div');
            row.className = 'ai-msg-row ' + (isUser ? 'is-user' : 'is-bot');

            const avatar = document.createElement('div');
            avatar.className = 'ai-avatar';
            avatar.innerHTML = '<i class="fas ' + (isUser ? 'fa-user' : 'fa-wand-magic-sparkles') + '"></i>';

            const wrap = document.createElement('div');
            wrap.className = 'ai-bubble-wrap';

            const bubble = document.createElement('div');
            bubble.className = 'ai-bubble';
            if (isUser) {
                bubble.textContent = msg.content;
            } else {
                bubble.innerHTML = md(msg.content || '');
            }

            const time = document.createElement('div');
            time.className = 'ai-msg-time';
            time.textContent = msg.diffForHumans || '';

            wrap.appendChild(bubble);
            wrap.appendChild(time);
            row.appendChild(avatar);
            row.appendChild(wrap);
            thread.appendChild(row);

            if (msg.id) {
                actions.filter(function(a) { return a.message_id == msg.id; }).forEach(function(a) {
                    thread.appendChild(renderAction(a));
                });
            }
        });

        thread.scrollTop = thread.scrollHeight;
    }

    function renderAction(action) {
        const card = document.createElement('div');
        card.className = 'ai-action-card is-' + (action.status || 'pending');

        const preview = action.preview || {};
        const fields  = Array.isArray(preview.fields) ? preview.fields : [];

        let inner =
            '<div class="ai-action-head">' +
                '<span class="ai-action-icon"><i class="fas fa-pen-to-square"></i></span>' +
                '<span class="ai-action-summary">' + escHtml(preview.summary || action.tool_name) + '</span>' +
                (action.status !== 'pending'
                    ? '<span class="ai-action-status is-' + escHtml(action.status) + '">' + statusLabel(action.status) + '</span>'
                    : '<span class="ai-action-status is-pending">' + L.pendingApproval + '</span>') +
            '</div>';

        if (fields.length > 0) {
            inner += '<div class="ai-action-fields">';
            fields.forEach(function(f) {
                inner += '<div class="ai-action-field"><span class="ai-action-field-label">' + escHtml(f.label) + '</span><span class="ai-action-field-value">' + escHtml(f.value) + '</span></div>';
            });
            inner += '</div>';
        }

        if (action.status === 'pending') {
            inner += '<div class="ai-action-buttons">';
            inner += '<button class="button is-success is-small" data-approve="' + action.id + '"><span class="icon"><i class="fas fa-check"></i></span><span>' + L.approve + '</span></button>';
            inner += '<button class="button is-small ai-btn-reject" data-reject="' + action.id + '"><span class="icon"><i class="fas fa-times"></i></span><span>' + L.reject + '</span></button>';
            inner += '</div>';
        }

        card.innerHTML = inner;

        const approveBtn = card.querySelector('[data-approve]');
        if (approveBtn) approveBtn.addEventListener('click', function() { updateAction(action.id, '/ai-chat/action/approve'); });
        const rejectBtn = card.querySelector('[data-reject]');
        if (rejectBtn) rejectBtn.addEventListener('click', function() { updateAction(action.id, '/ai-chat/action/reject'); });

        return card;
    }

    /* ---- helpers ---- */

    function setBusy(busy) {
        sendBtn.disabled = busy;
        sendBtn.innerHTML = busy
            ? '<i class="fas fa-spinner fa-spin"></i>'
            : '<i class="fas fa-paper-plane"></i>';
    }

    function statusLabel(s) {
        return s === 'approved' ? L.approved : s === 'rejected' ? L.rejected : s === 'failed' ? L.failed : s;
    }

    function showError(err) { alert(err.message || String(err)); }

    function origin() { return window.location.origin; }

    function escHtml(v) {
        return String(v == null ? '' : v).replace(/[&<>'"]/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c];
        });
    }
});
</script>
