{{-- Holographic HUD around the Body Map figure. Decorative and non-interactive; shown only in the Modern view. --}}
<div class="bm-hud" aria-hidden="true">
    <svg class="bm-hud-grid" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <defs>
            <pattern id="bm-hud-grid-pattern" width="8" height="8" patternUnits="userSpaceOnUse">
                <path d="M8 0H0V8" fill="none" class="bm-hud-grid-line"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#bm-hud-grid-pattern)"/>
    </svg>

    <span class="bm-corner bm-corner-tl"></span>
    <span class="bm-corner bm-corner-tr"></span>
    <span class="bm-corner bm-corner-bl"></span>
    <span class="bm-corner bm-corner-br"></span>

    <div class="bm-hud-scan">
        <span class="bm-hud-title">SCANNING...</span>
        <span class="bm-hud-progress"><i></i></span>
    </div>

    <div class="bm-hud-tag bm-hud-mode">
        <span>BODY SCAN</span>
        <span class="bm-hud-sub" data-hud="mode">FRONT</span>
    </div>

    <div class="bm-hud-widgets">
        <div class="bm-hud-widget bm-hud-pulse">
            <div class="bm-hud-row">
                <span class="bm-hud-label">PULSE RATE</span>
                <span class="bm-hud-value"><b data-hud="bpm">--</b> <small>BPM</small></span>
            </div>
            <svg class="bm-ecg" viewBox="0 0 160 36" preserveAspectRatio="none" focusable="false">
                <g class="bm-ecg-track" data-hud="ecg">
                    <path d="M0 18 H14 L18 18 L21 10 L25 26 L29 4 L33 32 L37 18 H52 L56 16 L60 18 H80
                             M80 18 H94 L98 18 L101 10 L105 26 L109 4 L113 32 L117 18 H132 L136 16 L140 18 H160"/>
                </g>
            </svg>
            <span class="bm-hud-note" data-hud="bpm-note">DEMO RHYTHM</span>
        </div>

        <div class="bm-hud-widget bm-hud-bars">
            <div class="bm-hud-bar">
                <div class="bm-hud-row">
                    <span class="bm-hud-label">MUSCLE STRENGTH</span>
                    <span class="bm-hud-value"><b data-hud="strength-value">--</b></span>
                </div>
                <div class="bm-segbar" data-hud="strength" data-segments="12">
                    @for ($i = 0; $i < 12; $i++)<i></i>@endfor
                </div>
            </div>
            <div class="bm-hud-bar">
                <div class="bm-hud-row">
                    <span class="bm-hud-label">MUSCLE CONDITION</span>
                    <span class="bm-hud-value"><b data-hud="condition-value">--</b></span>
                </div>
                <div class="bm-segbar" data-hud="condition" data-segments="12">
                    @for ($i = 0; $i < 12; $i++)<i></i>@endfor
                </div>
            </div>
            <span class="bm-hud-note" data-hud="bars-note">ILLUSTRATIVE</span>
        </div>
    </div>
</div>

<div class="bm-hud-callout" data-hud="callout" hidden aria-hidden="true">
    <svg class="bm-callout-svg" focusable="false">
        <circle class="bm-ring" r="14"/>
        <circle class="bm-ring bm-ring-delay" r="14"/>
        <path class="bm-callout-line"/>
        <circle class="bm-callout-dot" r="2.6"/>
    </svg>
    <span class="bm-callout-label"></span>
</div>
