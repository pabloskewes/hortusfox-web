<style>
.ai-chat-shell { min-height: 72vh; }
.ai-chat-sidebar { border-right: 1px solid rgba(127,127,127,0.2); }

/* Session list */
.ai-session-item { display: flex; align-items: center; gap: 0.55rem; padding: 0.65rem 0.75rem; border-radius: 8px; cursor: pointer; transition: background 0.15s; }
.ai-session-item:hover { background: rgba(127,127,127,0.12); }
.ai-session-item.is-active { background: rgba(72,199,142,0.18); }
.ai-session-icon { width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; flex-shrink: 0; }
.ai-session-body { overflow: hidden; }
.ai-session-title { font-weight: 600; font-size: 0.83rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ai-session-date { font-size: 0.68rem; opacity: 0.5; }

/* Thread */
.ai-thread { min-height: 52vh; max-height: 60vh; overflow-y: auto; padding: 1.25rem 0.75rem; display: flex; flex-direction: column; gap: 0.85rem; scroll-behavior: smooth; }

/* Message row */
.ai-msg-row { display: flex; align-items: flex-start; gap: 0.6rem; }
.ai-msg-row.is-user { flex-direction: row-reverse; }

/* Avatar */
.ai-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0; margin-top: 2px; }
.ai-avatar-user { background: #48c78e; color: #082016; }
.ai-avatar-bot { background: #3d4350; color: #8ab4f8; border: 1px solid rgba(255,255,255,0.12); }

/* Bubble — min-width:0 lets flex children shrink so tables don't blow out the row */
.ai-bubble-wrap { max-width: 80%; min-width: 0; }
.ai-bubble { padding: 0.85rem 1.05rem; border-radius: 16px; font-size: 0.93rem; line-height: 1.65; overflow-x: auto; }
.is-user .ai-bubble { background: #48c78e; color: #082016; border-bottom-right-radius: 3px; word-break: break-word; }
.is-assistant .ai-bubble { background: #2e3340; color: #dde2ea; border-bottom-left-radius: 3px; border: 1px solid rgba(255,255,255,0.07); }
.ai-msg-time { font-size: 0.68rem; opacity: 0.45; margin-top: 0.25rem; padding: 0 0.2rem; }
.is-user .ai-msg-time { text-align: right; }

/* Markdown inside bot bubbles — use [style] attribute selector to scope to our inline-styled bubbles */
.ai-from-bot p { margin: 0 0 0.5rem !important; color: #dde2ea !important; }
.ai-from-bot p:last-child { margin-bottom: 0 !important; }
.ai-from-bot h1,.ai-from-bot h2,.ai-from-bot h3 { font-weight: 700 !important; margin: 0.6rem 0 0.25rem !important; color: #dde2ea !important; }
.ai-from-bot ul,.ai-from-bot ol { padding-left: 1.25rem !important; margin: 0.2rem 0 0.45rem !important; color: #dde2ea !important; }
.ai-from-bot li { margin-bottom: 0.15rem !important; color: #dde2ea !important; }
.ai-from-bot strong { color: #ffffff !important; font-weight: 700 !important; }
.ai-from-bot em { color: #c8d8f0 !important; font-style: italic !important; }
.ai-from-bot code { background: rgba(0,0,0,0.35) !important; color: #e0e0e0 !important; padding: 0.1em 0.38em !important; border-radius: 4px !important; font-size: 0.82rem !important; font-family: monospace !important; }
.ai-from-bot pre { background: rgba(0,0,0,0.4) !important; padding: 0.75rem !important; border-radius: 8px !important; overflow-x: auto !important; margin: 0.45rem 0 !important; }
.ai-from-bot pre code { background: none !important; padding: 0 !important; }
.ai-from-bot table { border-collapse: collapse !important; width: auto !important; min-width: 100% !important; margin: 0.45rem 0 !important; font-size: 0.82rem !important; white-space: nowrap !important; }
.ai-from-bot th,.ai-from-bot td { border: 1px solid rgba(255,255,255,0.15) !important; padding: 0.3rem 0.65rem !important; color: #dde2ea !important; }
.ai-from-bot th { background: rgba(255,255,255,0.08) !important; font-weight: 600 !important; }
.ai-from-bot blockquote { border-left: 3px solid #48c78e !important; margin: 0.4rem 0 !important; padding: 0.25rem 0.75rem !important; opacity: 0.85 !important; }
.ai-from-bot hr { border: none !important; border-top: 1px solid rgba(255,255,255,0.12) !important; margin: 0.5rem 0 !important; }
.ai-from-bot a { color: #7ec8a0 !important; text-decoration: underline !important; }

/* Pending action card */
.ai-action-card { margin: 0.25rem 0 0 calc(30px + 0.6rem); border: 1px solid #ffdd57; border-radius: 10px; padding: 0.85rem 1rem; background: rgba(255,221,87,0.09); max-width: calc(78% + 30px + 0.6rem); }
.ai-action-summary { font-weight: 600; font-size: 0.87rem; margin-bottom: 0.5rem; }
.ai-action-fields { display: flex; flex-direction: column; gap: 0.2rem; margin-bottom: 0.65rem; }
.ai-action-field { display: flex; gap: 0.5rem; font-size: 0.82rem; }
.ai-action-field-label { opacity: 0.65; min-width: 7rem; }

/* Empty state */
.ai-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 52vh; opacity: 0.4; gap: 0.5rem; text-align: center; }
.ai-empty i { font-size: 2.8rem; }
.ai-empty p { font-size: 0.9rem; }

/* Input area */
.ai-input-area { border-top: 1px solid rgba(127,127,127,0.15); padding-top: 0.85rem; margin-top: 0.25rem; }

@media (max-width: 768px) {
    .ai-chat-sidebar { border-right: 0; border-bottom: 1px solid rgba(127,127,127,0.2); margin-bottom: 0.75rem; }
    .ai-bubble-wrap { max-width: 90%; }
    .ai-thread { max-height: 50vh; }
    .ai-action-card { max-width: 100%; margin-left: 0; }
}
</style>

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

            <div class="column is-9" style="display:flex;flex-direction:column;">
                <div class="ai-thread" id="ai-thread">
                    <div class="ai-empty" id="ai-empty-state">
                        <i class="fas fa-seedling"></i>
                        <p>{{ __('app.ai_chat_empty_state') }}</p>
                    </div>
                </div>

                <div class="ai-input-area">
                    <form id="ai-chat-form">
                        <div class="field has-addons" style="margin-bottom:0;">
                            <div class="control is-expanded">
                                <textarea class="textarea is-input-dark" id="ai-input" rows="2"
                                    placeholder="{{ __('app.ai_chat_placeholder') }}"
                                    style="resize:none;border-radius:10px 0 0 10px;"></textarea>
                            </div>
                            <div class="control">
                                <button class="button is-success" id="ai-send" type="submit"
                                    style="height:100%;border-radius:0 10px 10px 0;padding:0 1.25rem;">
                                    <span class="icon"><i class="fas fa-paper-plane"></i></span>
                                </button>
                            </div>
                        </div>
                        <p class="help" style="opacity:0.4;margin-top:0.3rem;">{{ __('app.ai_chat_send_hint') }}</p>
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
        send:            '{{ __('app.send') }}'
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

    loadSessions();

    /* ---- data ---- */

    function loadSessions() {
        window.axios.get(origin() + '/ai-chat/sessions').then(function(r) {
            if (r.data.code !== 200) throw new Error(r.data.msg);
            sessions = r.data.sessions;
            if (!currentSessionId && sessions.length > 0) {
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
        sessions.forEach(function(s) {
            const el = document.createElement('div');
            el.className = 'ai-session-item' + (s.id == currentSessionId ? ' is-active' : '');
            el.innerHTML =
                '<div class="ai-session-icon"><i class="fas fa-leaf"></i></div>' +
                '<div class="ai-session-body">' +
                    '<div class="ai-session-title">' + escHtml(s.title) + '</div>' +
                    '<div class="ai-session-date">' + escHtml(s.updated_at || '') + '</div>' +
                '</div>';
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
            row.className = 'ai-msg-row ' + (isUser ? 'ai-from-user' : 'ai-from-bot');
            row.style.cssText = 'display:flex;align-items:flex-start;gap:0.65rem;' + (isUser ? 'flex-direction:row-reverse;' : '');

            const avatar = document.createElement('div');
            avatar.style.cssText = 'width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;margin-top:2px;' + (isUser ? 'background:#48c78e;color:#082016;' : 'background:#3d4350;color:#8ab4f8;border:1px solid rgba(255,255,255,0.15);');
            avatar.innerHTML = '<i class="fas ' + (isUser ? 'fa-user' : 'fa-seedling') + '"></i>';

            const wrap = document.createElement('div');
            wrap.style.cssText = 'max-width:78%;min-width:0;';

            const bubble = document.createElement('div');
            bubble.style.cssText = 'padding:0.85rem 1.1rem;border-radius:16px;font-size:0.93rem;line-height:1.65;overflow-x:auto;' + (isUser ? 'background:#48c78e;color:#082016;border-bottom-right-radius:3px;word-break:break-word;' : 'background:#2e3340;color:#dde2ea;border-bottom-left-radius:3px;border:1px solid rgba(255,255,255,0.08);');
            if (isUser) {
                bubble.textContent = msg.content;
            } else {
                bubble.innerHTML = md(msg.content || '');
            }

            const time = document.createElement('div');
            time.className = 'ai-msg-time';
            time.style.cssText = 'font-size:0.68rem;opacity:0.45;margin-top:0.25rem;padding:0 0.2rem;' + (isUser ? 'text-align:right;' : '');
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
        card.className = 'ai-action-card';

        const preview = action.preview || {};
        const fields  = Array.isArray(preview.fields) ? preview.fields : [];

        let inner = '<div class="ai-action-summary"><span class="icon-text"><span class="icon"><i class="fas fa-bolt"></i></span><span>' + escHtml(preview.summary || action.tool_name) + '</span></span></div>';

        if (fields.length > 0) {
            inner += '<div class="ai-action-fields">';
            fields.forEach(function(f) {
                inner += '<div class="ai-action-field"><span class="ai-action-field-label">' + escHtml(f.label) + '</span><span>' + escHtml(f.value) + '</span></div>';
            });
            inner += '</div>';
        }

        inner += '<div class="buttons are-small">';
        if (action.status === 'pending') {
            inner += '<button class="button is-success" data-approve="' + action.id + '"><span class="icon"><i class="fas fa-check"></i></span><span>' + L.approve + '</span></button>';
            inner += '<button class="button is-danger is-outlined" data-reject="' + action.id + '"><span class="icon"><i class="fas fa-times"></i></span><span>' + L.reject + '</span></button>';
        } else {
            inner += '<span class="tag ' + statusCls(action.status) + '">' + statusLabel(action.status) + '</span>';
        }
        inner += '</div>';

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
            ? '<span class="icon"><i class="fas fa-spinner fa-spin"></i></span>'
            : '<span class="icon"><i class="fas fa-paper-plane"></i></span>';
    }

    function statusCls(s) {
        return s === 'approved' ? 'is-success' : s === 'rejected' ? 'is-danger' : 'is-warning';
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
