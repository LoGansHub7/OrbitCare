{{-- ORBIT assistant: floating launcher (every astronaut page) or inline page mode (/assistant). All behaviour lives in public/chatbot/assistant.js. --}}
@props(['mode' => 'floating'])
{{-- Staff see the launcher in local-only mode: no crew snapshot and no server sync. --}}
@if (auth()->check() && (auth()->user()->isAstronaut() || auth()->user()->isMedicalOfficer() || auth()->user()->isAdmin()))
<div class="oc-orbit oc-orbit-{{ $mode }}" id="orbit-assistant" data-orbit-root data-mode="{{ $mode }}"
     data-owner="{{ auth()->id() }}" data-sync="{{ auth()->user()->isAstronaut() ? '1' : '0' }}" data-base="{{ asset('') }}" data-assets="{{ asset('chatbot') }}/"
     data-page="{{ request()->route()?->getName() }}">

    @if ($mode === 'floating')
        <button type="button" class="oc-orbit-launcher" data-orbit-launch aria-expanded="false" aria-controls="orbit-panel" aria-label="Open ORBIT assistant">
            <span class="oc-orbit-ring" aria-hidden="true"></span>
            <span class="oc-orbit-orb" aria-hidden="true"></span>
            <span class="oc-orbit-dot" data-orbit-dot aria-hidden="true"></span>
            <span class="oc-orbit-badge" data-orbit-badge hidden>0</span>
        </button>
    @endif

    <section id="orbit-panel" class="oc-orbit-panel" role="dialog" aria-labelledby="orbit-title"
             aria-modal="{{ $mode === 'floating' ? 'true' : 'false' }}" data-orbit-panel @if ($mode === 'floating') hidden @endif>
        <header class="oc-orbit-head">
            <div class="oc-orbit-avatar" aria-hidden="true"><span></span></div>
            <div class="oc-orbit-titles">
                <h2 id="orbit-title" class="oc-orbit-title">ORBIT</h2>
                <p class="oc-orbit-state" aria-live="polite"><span class="oc-orbit-dot" data-orbit-dot aria-hidden="true"></span> <span data-orbit-status>Connecting…</span></p>
            </div>
            <div class="oc-orbit-tools">
                <button type="button" class="oc-orbit-tool" data-orbit-speak aria-pressed="false" aria-label="Read replies aloud" title="Read replies aloud" hidden><i class="bi bi-volume-up" aria-hidden="true"></i></button>
                <button type="button" class="oc-orbit-tool" data-orbit-clear aria-label="Clear chat on this device" title="Clear chat on this device"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                @if ($mode === 'floating')
                    <button type="button" class="oc-orbit-tool" data-orbit-minimise aria-expanded="true" aria-label="Minimise ORBIT" title="Minimise"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <button type="button" class="oc-orbit-tool" data-orbit-close aria-label="Close ORBIT" title="Close (Esc)"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                @endif
            </div>
        </header>

        <div class="oc-orbit-body">
            <div class="oc-orbit-log" data-orbit-log role="log" aria-live="polite" aria-relevant="additions" tabindex="0" aria-label="Conversation with ORBIT"></div>
            <div class="oc-orbit-chips" data-orbit-chips role="group" aria-label="Suggested questions"></div>
            <div class="oc-orbit-menu" data-orbit-menu hidden role="group" aria-label="Topics you can ask about"></div>
        </div>

        <form class="oc-orbit-form" data-orbit-form autocomplete="off">
            <button type="button" class="oc-orbit-pill" data-orbit-menu-toggle aria-expanded="false" aria-controls="orbit-menu-panel">
                <i class="bi bi-grid" aria-hidden="true"></i> What can I ask?
            </button>
            <label class="visually-hidden" for="orbit-input">Ask ORBIT a question</label>
            <input id="orbit-input" class="oc-orbit-input" data-orbit-input type="text" maxlength="500" placeholder="Ask about sleep, vitals, radiation…">
            <button type="button" class="oc-orbit-icon" data-orbit-mic aria-label="Speak your question" title="Speak your question" hidden><i class="bi bi-mic" aria-hidden="true"></i></button>
            <button type="submit" class="oc-orbit-icon oc-orbit-send" aria-label="Send"><i class="bi bi-send" aria-hidden="true"></i></button>
        </form>

        <p class="oc-orbit-note">ORBIT supports your mission medical team and does not replace them. It works offline; chats are kept on this device and synced when you are online.</p>
    </section>
</div>
@endif
