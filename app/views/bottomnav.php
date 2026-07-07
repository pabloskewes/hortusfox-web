<?php $bottomnav_path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); ?>

<div class="bottomnav">
    <div class="bottomnav-items">
        <div class="bottomnav-item {{ (strpos($bottomnav_path, '/plants') === 0) ? 'is-active' : '' }}">
            <a href="{{ url('/plants') }}">
                <div><i class="fas fa-leaf"></i></div>
                <div>{{ __('app.nav_plants_short') }}</div>
            </a>
        </div>

        @if (AiChatConfigModule::isEnabled())
        <div class="bottomnav-item {{ (strpos($bottomnav_path, '/ai-chat') === 0) ? 'is-active' : '' }}">
            <a href="{{ url('/ai-chat') }}">
                <div><i class="fas fa-wand-magic-sparkles"></i></div>
                <div>{{ __('app.ai_chat_short') }}</div>
            </a>
        </div>
        @endif

        @if (app('tasks_enable'))
        <div class="bottomnav-item {{ (strpos($bottomnav_path, '/tasks') === 0) ? 'is-active' : '' }}">
            <a href="{{ url('/tasks') }}">
                <div><i class="fas fa-tasks"></i></div>
                <div>{{ __('app.tasks') }}</div>
            </a>
        </div>
        @endif

        <div class="bottomnav-item {{ (strpos($bottomnav_path, '/search') === 0) ? 'is-active' : '' }}">
            <a href="{{ url('/search') }}">
                <div><i class="fas fa-search"></i></div>
                <div>{{ __('app.search') }}</div>
            </a>
        </div>

        @if (app('calendar_enable'))
        <div class="bottomnav-item {{ (strpos($bottomnav_path, '/calendar') === 0) ? 'is-active' : '' }}">
            <a href="{{ url('/calendar') }}">
                <div><i class="far fa-calendar-alt"></i></div>
                <div>{{ __('app.calendar') }}</div>
            </a>
        </div>
        @endif
    </div>
</div>
