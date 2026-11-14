<svg class="bm-svg" data-bodymap-svg viewBox="0 0 300 640" preserveAspectRatio="xMidYMid meet"
     role="group" aria-label="Interactive human body map. Select a highlighted region for details.">

    {{-- SKIN (silhouette) --}}
    <g class="bm-region" data-region="skin" data-layer="skin" tabindex="0" role="button" aria-label="Skin">
        <ellipse cx="150" cy="50" rx="27" ry="33"/>
        <path d="M138 80 L138 102 L162 102 L162 80 Z"/>
        <path d="M104 104 Q150 94 196 104 L206 112 Q214 130 210 170 L204 250 Q200 280 196 296 L104 296 Q100 280 96 250 L90 170 Q86 130 94 112 Z"/>
        <path d="M94 112 Q76 118 72 140 L62 215 Q58 235 60 250 L52 320 Q50 335 58 340 Q70 344 72 332 L84 262 Q88 240 92 215 L98 150 Z"/>
        <path d="M206 112 Q224 118 228 140 L238 215 Q242 235 240 250 L248 320 Q250 335 242 340 Q230 344 228 332 L216 262 Q212 240 208 215 L202 150 Z"/>
        <path d="M100 292 L148 292 Q150 340 146 400 Q144 450 138 490 L134 570 Q134 600 140 614 L106 614 Q108 596 110 570 L108 490 Q100 440 98 380 Q96 330 100 292 Z"/>
        <path d="M200 292 L152 292 Q150 340 154 400 Q156 450 162 490 L166 570 Q166 600 160 614 L194 614 Q192 596 190 570 L192 490 Q200 440 202 380 Q204 330 200 292 Z"/>
    </g>

    {{-- MUSCLE layer --}}
    <g class="bm-region bm-v-front" data-region="upper-muscles" data-layer="muscle" tabindex="0" role="button" aria-label="Upper-body muscles">
        <path d="M96 112 Q78 120 76 150 L70 205 Q82 210 90 204 L98 150 Z"/>
        <path d="M204 112 Q222 120 224 150 L230 205 Q218 210 210 204 L202 150 Z"/>
        <path d="M108 116 Q146 112 148 124 L148 176 Q120 184 104 168 Q100 140 108 116 Z"/>
        <path d="M192 116 Q154 112 152 124 L152 176 Q180 184 196 168 Q200 140 192 116 Z"/>
        <path d="M68 222 Q60 250 58 300 L68 304 Q76 262 84 222 Z"/>
        <path d="M232 222 Q240 250 242 300 L232 304 Q224 262 216 222 Z"/>
        <path d="M124 190 L176 190 L172 280 L128 280 Z"/>
    </g>
    <g class="bm-region bm-v-back" data-region="upper-muscles" data-layer="muscle" tabindex="0" role="button" aria-label="Upper-body muscles">
        <path d="M150 104 Q120 108 104 118 Q98 170 112 240 L150 262 L188 240 Q202 170 196 118 Q180 108 150 104 Z"/>
        <path d="M96 112 Q78 120 76 150 L70 205 Q82 210 90 204 L98 150 Z"/>
        <path d="M204 112 Q222 120 224 150 L230 205 Q218 210 210 204 L202 150 Z"/>
        <path d="M68 222 Q60 250 58 300 L68 304 Q76 262 84 222 Z"/>
        <path d="M232 222 Q240 250 242 300 L232 304 Q224 262 216 222 Z"/>
    </g>

    <g class="bm-region bm-v-front" data-region="lower-muscles" data-layer="muscle" tabindex="0" role="button" aria-label="Lower-body muscles">
        <path d="M106 302 L146 302 Q148 360 142 428 Q124 436 112 428 Q102 370 106 302 Z"/>
        <path d="M194 302 L154 302 Q152 360 158 428 Q176 436 188 428 Q198 370 194 302 Z"/>
        <path d="M112 452 Q106 480 112 530 L130 530 Q138 480 134 452 Q124 446 112 452 Z"/>
        <path d="M188 452 Q194 480 188 530 L170 530 Q162 480 166 452 Q176 446 188 452 Z"/>
    </g>
    <g class="bm-region bm-v-back" data-region="lower-muscles" data-layer="muscle" tabindex="0" role="button" aria-label="Lower-body muscles">
        <path d="M104 292 Q150 280 196 292 Q200 330 150 340 Q100 330 104 292 Z"/>
        <path d="M106 340 Q104 390 112 428 Q124 436 142 428 Q148 380 146 340 Z"/>
        <path d="M194 340 Q196 390 188 428 Q176 436 158 428 Q152 380 154 340 Z"/>
        <path d="M112 452 Q106 480 112 530 L130 530 Q138 480 134 452 Q124 446 112 452 Z"/>
        <path d="M188 452 Q194 480 188 530 L170 530 Q162 480 166 452 Q176 446 188 452 Z"/>
    </g>

    {{-- SKELETON layer --}}
    <g class="bm-deco bm-v-front" data-layer="skeleton" aria-hidden="true">
        <path d="M148 124 Q118 126 112 146 M148 140 Q116 142 110 162 M148 156 Q116 158 112 178 M148 172 Q120 174 116 192
                 M152 124 Q182 126 188 146 M152 140 Q184 142 190 162 M152 156 Q184 158 188 178 M152 172 Q180 174 184 192"/>
    </g>

    <g class="bm-region" data-region="spine" data-layer="skeleton" tabindex="0" role="button" aria-label="Spine">
        <path class="bm-hit" d="M138 100 L162 100 L162 290 L138 290 Z"/>
        <path class="bm-line bm-spine" d="M150 100 L150 284"/>
        <path d="M118 282 Q150 300 182 282 L178 310 Q150 322 122 310 Z"/>
    </g>

    <g class="bm-region" data-region="long-bones" data-layer="skeleton" tabindex="0" role="button" aria-label="Long bones">
        <path class="bm-line bm-bone" d="M84 118 L70 215 M70 217 L58 320 M216 118 L230 215 M230 217 L242 320 M122 300 L122 440 M122 446 L124 600 M178 300 L178 440 M178 446 L176 600"/>
        <circle cx="84" cy="118" r="5"/><circle cx="70" cy="216" r="4.5"/><circle cx="58" cy="322" r="4"/>
        <circle cx="216" cy="118" r="5"/><circle cx="230" cy="216" r="4.5"/><circle cx="242" cy="322" r="4"/>
        <circle cx="122" cy="443" r="5.5"/><circle cx="178" cy="443" r="5.5"/>
    </g>

    {{-- ORGANS layer --}}
    <g class="bm-region" data-region="head" data-layer="organs" tabindex="0" role="button" aria-label="Head and brain">
        <ellipse class="bm-hit" cx="150" cy="38" rx="26" ry="28"/>
        <path d="M128 36 Q126 18 150 16 Q174 18 172 36 Q164 44 150 42 Q136 44 128 36 Z"/>
        <path class="bm-deco" d="M150 18 L150 40 M138 22 Q142 30 138 38 M162 22 Q158 30 162 38"/>
    </g>

    <g class="bm-region bm-v-front" data-region="eyes" data-layer="organs" tabindex="0" role="button" aria-label="Eyes">
        <ellipse class="bm-hit" cx="150" cy="52" rx="26" ry="9"/>
        <ellipse cx="139" cy="52" rx="7" ry="4.5"/>
        <ellipse cx="161" cy="52" rx="7" ry="4.5"/>
        <circle class="bm-pupil" cx="139" cy="52" r="1.8"/>
        <circle class="bm-pupil" cx="161" cy="52" r="1.8"/>
    </g>

    <g class="bm-region bm-v-front" data-region="lungs" data-layer="organs" tabindex="0" role="button" aria-label="Lungs">
        <path class="bm-breathe" d="M114 126 Q104 150 108 176 Q112 184 128 180 Q144 170 146 140 Q144 120 128 118 Z"/>
        <path class="bm-breathe" d="M186 126 Q196 150 192 176 Q188 184 172 180 Q156 170 154 140 Q156 120 172 118 Z"/>
        <path class="bm-deco" d="M150 118 L150 142 M150 142 L134 158 M150 142 L166 158"/>
    </g>

    <g class="bm-region bm-v-front" data-region="heart" data-layer="organs" tabindex="0" role="button" aria-label="Heart">
        <path class="bm-beat" d="M162 150 C154 142 142 146 144 158 C146 168 158 176 162 184 C166 176 178 168 180 158 C182 146 170 142 162 150 Z"/>
    </g>

    <g class="bm-region bm-v-front" data-region="digestive" data-layer="organs" tabindex="0" role="button" aria-label="Digestive system">
        <path d="M118 200 Q116 232 124 256 Q150 270 176 256 Q184 232 182 200 Q150 190 118 200 Z"/>
        <path class="bm-deco" d="M130 214 Q150 206 170 214 M128 232 Q150 224 172 232 M132 248 Q150 242 168 248"/>
    </g>

    {{-- Status markers are drawn here by bodymap.js --}}
    <g data-bodymap-markers></g>
</svg>