<svg class="bm-svg" data-bodymap-svg viewBox="0 0 300 640" preserveAspectRatio="xMidYMid meet"
     role="group" aria-label="Interactive human body map. Select a highlighted region for details.">

    {{-- Hologram paint servers. Used only by the .bm-holo styles; harmless in the Classic view. --}}
    <defs class="bm-holo-defs">
        <path id="bm-holo-sil" d="M143 18 Q150 17 157 18 Q164 19 168.5 24 Q173 29 174.5 37 Q176 45 174.5 52.5 Q173 60 170 66 Q167 72 164.5 76 Q162 80 162 85 Q162 90 163 95 Q164 100 164 100 Q164 100 171 102.5 Q178 105 187 107.5 Q196 110 202 114 Q208 118 211 125 Q214 132 217 144 Q220 156 223 171 Q226 186 228.5 200 Q231 214 232.5 222 Q234 230 236 246 Q238 262 240.5 279 Q243 296 245 309 Q247 322 249.5 329 Q252 336 252.5 341 Q253 346 251 348.5 Q249 351 246.5 348.5 Q244 346 242.5 342 Q241 338 240.5 338 Q240 338 238 327 Q236 316 233 300 Q230 284 226.5 268 Q223 252 219.5 239 Q216 226 213 216 Q210 206 207.5 196 Q205 186 203.5 176 Q202 166 202 166 Q202 166 201 181 Q200 196 198 214 Q196 232 194.5 245 Q193 258 194.5 271 Q196 284 199.5 293 Q203 302 204 321 Q205 340 204 360 Q203 380 201 399 Q199 418 196.5 434 Q194 450 193 462 Q192 474 193 492 Q194 510 194.5 529 Q195 548 193.5 567 Q192 586 192.5 595 Q193 604 195.5 609 Q198 614 198 614 Q198 614 197 618 Q196 622 189 623 Q182 624 175 623 Q168 622 166.5 619 Q165 616 165 616 Q165 616 165 606 Q165 596 165.5 581 Q166 566 165 545 Q164 524 162 504 Q160 484 159 468 Q158 452 157 436 Q156 420 154.5 400 Q153 380 152 360 Q151 340 150.5 329 Q150 318 149.5 329 Q149 340 148 360 Q147 380 145.5 400 Q144 420 143 436 Q142 452 141 468 Q140 484 138 504 Q136 524 135 545 Q134 566 134.5 581 Q135 596 135 606 Q135 616 135 616 Q135 616 133.5 619 Q132 622 125 623 Q118 624 111 623 Q104 622 103 618 Q102 614 102 614 Q102 614 104.5 609 Q107 604 107.5 595 Q108 586 106.5 567 Q105 548 105.5 529 Q106 510 107 492 Q108 474 107 462 Q106 450 103.5 434 Q101 418 99 399 Q97 380 96 360 Q95 340 96 321 Q97 302 100.5 293 Q104 284 105.5 271 Q107 258 105.5 245 Q104 232 102 214 Q100 196 99 181 Q98 166 98 166 Q98 166 96.5 176 Q95 186 92.5 196 Q90 206 87 216 Q84 226 80.5 239 Q77 252 73.5 268 Q70 284 67 300 Q64 316 62 327 Q60 338 59.5 338 Q59 338 57.5 342 Q56 346 53.5 348.5 Q51 351 49 348.5 Q47 346 47.5 341 Q48 336 50.5 329 Q53 322 55 309 Q57 296 59.5 279 Q62 262 64 246 Q66 230 67.5 222 Q69 214 71.5 200 Q74 186 77 171 Q80 156 83 144 Q86 132 89 125 Q92 118 98 114 Q104 110 113 107.5 Q122 105 129 102.5 Q136 100 136 100 Q136 100 137 95 Q138 90 138 85 Q138 80 135.5 76 Q133 72 130 66 Q127 60 125.5 52.5 Q124 45 125.5 37 Q127 29 131.5 24 Q136 19 143 18 Z"/>
        <clipPath id="bm-holo-body-clip"><use href="#bm-holo-sil"/></clipPath>
        <linearGradient id="bm-holo-body" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" class="bm-holo-stop-a" stop-opacity=".5"/>
            <stop offset=".55" class="bm-holo-stop-a" stop-opacity=".22"/>
            <stop offset="1" class="bm-holo-stop-b" stop-opacity=".05"/>
        </linearGradient>
        <linearGradient id="bm-holo-line" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" class="bm-holo-stop-a"/>
            <stop offset="1" class="bm-holo-stop-b"/>
        </linearGradient>
        <linearGradient id="bm-holo-scan" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" class="bm-holo-stop-a" stop-opacity="0"/>
            <stop offset=".5" class="bm-holo-stop-a" stop-opacity=".5"/>
            <stop offset="1" class="bm-holo-stop-a" stop-opacity="0"/>
        </linearGradient>
        <pattern id="bm-holo-hatch" width="3.4" height="3.4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <line x1="0" y1="0" x2="0" y2="3.4" class="bm-holo-hatch-line"/>
        </pattern>
        <filter id="bm-holo-glow" x="-15%" y="-8%" width="130%" height="116%" color-interpolation-filters="sRGB">
            <feGaussianBlur in="SourceGraphic" stdDeviation="2.2" result="blur"/>
            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <filter id="bm-holo-blur" x="-20%" y="-10%" width="140%" height="120%" color-interpolation-filters="sRGB">
            <feGaussianBlur stdDeviation="5"/>
        </filter>
    </defs>

    {{-- Hologram body: rim light, hatching and contour mesh under the silhouette. --}}
    <g class="bm-holo-fx" data-layer="skin" aria-hidden="true" pointer-events="none">
        <g clip-path="url(#bm-holo-body-clip)">
            <use href="#bm-holo-sil" class="bm-holo-rim" fill="none" stroke-width="16" filter="url(#bm-holo-blur)"/>
            <use href="#bm-holo-sil" fill="url(#bm-holo-hatch)" class="bm-holo-hatch"/>
            <g class="bm-holo-lines bm-holo-mesh">
                <path d="M30 118 Q150 128 270 118 M30 134 Q150 146 270 134 M30 152 Q150 164 270 152 M30 170 Q150 182 270 170 M30 190 Q150 202 270 190 M30 212 Q150 224 270 212 M30 236 Q150 248 270 236 M30 260 Q150 272 270 260 M30 284 Q150 296 270 284"/>
                <path d="M92 330 Q122 338 152 330 M148 330 Q178 338 208 330 M92 360 Q122 368 152 360 M148 360 Q178 368 208 360 M92 392 Q122 400 152 392 M148 392 Q178 400 208 392 M92 424 Q122 432 152 424 M148 424 Q178 432 208 424 M92 470 Q122 478 152 470 M148 470 Q178 478 208 470 M92 510 Q122 518 152 510 M148 510 Q178 518 208 510 M92 550 Q122 558 152 550 M148 550 Q178 558 208 550 M92 588 Q122 595 152 588 M148 588 Q178 595 208 588"/>
                <path d="M62 330 Q54 336 46 332 M62 300 Q70 306 78 300 M66 276 Q74 282 82 276 M72 250 Q80 256 88 250 M80 222 Q88 228 96 222 M238 330 Q246 336 254 332 M238 300 Q230 306 222 300 M234 276 Q226 282 218 276 M228 250 Q220 256 212 250 M220 222 Q212 228 204 222"/>
                <path d="M126 22 Q150 30 174 22 M124 40 Q150 48 176 40 M126 58 Q150 66 174 58 M137 84 Q150 90 163 84"/>
            </g>
        </g>
    </g>

    {{-- SKIN (silhouette) --}}
    <g class="bm-region" data-region="skin" data-layer="skin" tabindex="0" role="button" aria-label="Skin">
        <path d="M143 18 Q150 17 157 18 Q164 19 168.5 24 Q173 29 174.5 37 Q176 45 174.5 52.5 Q173 60 170 66 Q167 72 164.5 76 Q162 80 162 85 Q162 90 163 95 Q164 100 164 100 Q164 100 171 102.5 Q178 105 187 107.5 Q196 110 202 114 Q208 118 211 125 Q214 132 217 144 Q220 156 223 171 Q226 186 228.5 200 Q231 214 232.5 222 Q234 230 236 246 Q238 262 240.5 279 Q243 296 245 309 Q247 322 249.5 329 Q252 336 252.5 341 Q253 346 251 348.5 Q249 351 246.5 348.5 Q244 346 242.5 342 Q241 338 240.5 338 Q240 338 238 327 Q236 316 233 300 Q230 284 226.5 268 Q223 252 219.5 239 Q216 226 213 216 Q210 206 207.5 196 Q205 186 203.5 176 Q202 166 202 166 Q202 166 201 181 Q200 196 198 214 Q196 232 194.5 245 Q193 258 194.5 271 Q196 284 199.5 293 Q203 302 204 321 Q205 340 204 360 Q203 380 201 399 Q199 418 196.5 434 Q194 450 193 462 Q192 474 193 492 Q194 510 194.5 529 Q195 548 193.5 567 Q192 586 192.5 595 Q193 604 195.5 609 Q198 614 198 614 Q198 614 197 618 Q196 622 189 623 Q182 624 175 623 Q168 622 166.5 619 Q165 616 165 616 Q165 616 165 606 Q165 596 165.5 581 Q166 566 165 545 Q164 524 162 504 Q160 484 159 468 Q158 452 157 436 Q156 420 154.5 400 Q153 380 152 360 Q151 340 150.5 329 Q150 318 149.5 329 Q149 340 148 360 Q147 380 145.5 400 Q144 420 143 436 Q142 452 141 468 Q140 484 138 504 Q136 524 135 545 Q134 566 134.5 581 Q135 596 135 606 Q135 616 135 616 Q135 616 133.5 619 Q132 622 125 623 Q118 624 111 623 Q104 622 103 618 Q102 614 102 614 Q102 614 104.5 609 Q107 604 107.5 595 Q108 586 106.5 567 Q105 548 105.5 529 Q106 510 107 492 Q108 474 107 462 Q106 450 103.5 434 Q101 418 99 399 Q97 380 96 360 Q95 340 96 321 Q97 302 100.5 293 Q104 284 105.5 271 Q107 258 105.5 245 Q104 232 102 214 Q100 196 99 181 Q98 166 98 166 Q98 166 96.5 176 Q95 186 92.5 196 Q90 206 87 216 Q84 226 80.5 239 Q77 252 73.5 268 Q70 284 67 300 Q64 316 62 327 Q60 338 59.5 338 Q59 338 57.5 342 Q56 346 53.5 348.5 Q51 351 49 348.5 Q47 346 47.5 341 Q48 336 50.5 329 Q53 322 55 309 Q57 296 59.5 279 Q62 262 64 246 Q66 230 67.5 222 Q69 214 71.5 200 Q74 186 77 171 Q80 156 83 144 Q86 132 89 125 Q92 118 98 114 Q104 110 113 107.5 Q122 105 129 102.5 Q136 100 136 100 Q136 100 137 95 Q138 90 138 85 Q138 80 135.5 76 Q133 72 130 66 Q127 60 125.5 52.5 Q124 45 125.5 37 Q127 29 131.5 24 Q136 19 143 18 Z"/>
    </g>

    {{-- Hologram skeleton detail (front): skull, ribs, clavicles, pelvis, joints, hands and feet. --}}
    <g class="bm-holo-deco bm-v-front" data-layer="skeleton" aria-hidden="true" pointer-events="none">
        <path d="M150 20 C136 20 127 30 127 44 C127 52 130 58 135 62 L135 70 Q150 78 165 70 L165 62 C170 58 173 52 173 44 C173 30 164 20 150 20 Z"/>
        <circle cx="140" cy="49" r="4.2"/><circle cx="160" cy="49" r="4.2"/>
        <path class="bm-holo-lines" d="M150 55 L147 63 L153 63 Z M141 70 Q150 74 159 70 M144 66 L144 70 M150 67 L150 71 M156 66 L156 70"/>
        <path class="bm-holo-lines" d="M143 84 H157 M143 90 H157 M143 96 H157"/>
        <path class="bm-holo-lines" d="M150 108 Q126 103 98 114 M150 108 Q174 103 202 114"/>
        <path d="M147 112 H153 V158 Q150 166 147 158 Z"/>
        <path class="bm-holo-lines" d="M147 122 Q104 118 110 138 Q112 152 140 156 M147 133 Q100 129 106 149 Q108 163 140 167 M147 144 Q99 140 105 160 Q107 174 140 178 M147 155 Q100 151 106 171 Q108 185 140 189 M147 166 Q103 162 109 182 Q111 195 140 199 M147 177 Q108 173 114 191 Q116 203 140 206
                 M153 122 Q196 118 190 138 Q188 152 160 156 M153 133 Q200 129 194 149 Q192 163 160 167 M153 144 Q201 140 195 160 Q193 174 160 178 M153 155 Q200 151 194 171 Q192 185 160 189 M153 166 Q197 162 191 182 Q189 195 160 199 M153 177 Q192 173 186 191 Q184 203 160 206"/>
        <path d="M150 282 Q118 276 104 290 Q100 304 112 318 Q124 326 138 322 Q148 316 150 306 Z M150 282 Q182 276 196 290 Q200 304 188 318 Q176 326 162 322 Q152 316 150 306 Z"/>
        <path d="M140 290 L160 290 L150 318 Z"/>
        <circle cx="100" cy="124" r="6"/><circle cx="200" cy="124" r="6"/>
        <circle cx="76" cy="223" r="5"/><circle cx="224" cy="223" r="5"/>
        <circle cx="60" cy="326" r="4"/><circle cx="240" cy="326" r="4"/>
        <circle cx="122" cy="443" r="6"/><circle cx="178" cy="443" r="6"/>
        <circle cx="122" cy="597" r="4"/><circle cx="178" cy="597" r="4"/>
        <path class="bm-holo-lines" d="M60 330 L56 346 M60 330 L60 349 M60 330 L64 347 M240 330 L244 346 M240 330 L240 349 M240 330 L236 347
                 M122 600 L110 619 M122 600 L118 621 M122 600 L127 621 M178 600 L190 619 M178 600 L182 621 M178 600 L173 621"/>
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
    <g class="bm-deco bm-classic-ribs bm-v-front" data-layer="skeleton" aria-hidden="true">
        <path d="M148 124 Q118 126 112 146 M148 140 Q116 142 110 162 M148 156 Q116 158 112 178 M148 172 Q120 174 116 192
                 M152 124 Q182 126 188 146 M152 140 Q184 142 190 162 M152 156 Q184 158 188 178 M152 172 Q180 174 184 192"/>
    </g>

    <g class="bm-region" data-region="spine" data-layer="skeleton" tabindex="0" role="button" aria-label="Spine">
        <path class="bm-hit" d="M138 100 L162 100 L162 290 L138 290 Z"/>
        <path class="bm-line bm-spine" d="M150 100 L150 284"/>
        <path d="M118 282 Q150 300 182 282 L178 310 Q150 322 122 310 Z"/>
    </g>

    <g class="bm-region" data-region="long-bones" data-layer="skeleton" tabindex="0" role="button" aria-label="Long bones">
        <path class="bm-line bm-bone" d="M100 124 L76 222 M76 224 L60 324 M200 124 L224 222 M224 224 L240 324 M122 300 L122 440 M122 446 L124 600 M178 300 L178 440 M178 446 L176 600"/>
        <circle cx="100" cy="124" r="5"/><circle cx="76" cy="223" r="4.5"/><circle cx="60" cy="326" r="4"/>
        <circle cx="200" cy="124" r="5"/><circle cx="224" cy="223" r="4.5"/><circle cx="240" cy="326" r="4"/>
        <circle cx="122" cy="443" r="5.5"/><circle cx="178" cy="443" r="5.5"/>
    </g>

    {{-- Hologram organ detail (front): brain folds, bronchi, vessels, liver, stomach, intestines, pulse halos. --}}
    <g class="bm-holo-deco bm-v-front" data-layer="organs" aria-hidden="true" pointer-events="none">
        <ellipse class="bm-halo bm-halo-lung" cx="125" cy="150" rx="26" ry="38"/>
        <ellipse class="bm-halo bm-halo-lung" cx="175" cy="150" rx="26" ry="38"/>
        <circle class="bm-halo bm-halo-heart" cx="162" cy="166" r="22"/>
        <path d="M118 196 Q128 190 150 196 Q160 200 160 212 Q150 224 134 226 Q120 222 114 208 Z"/>
        <path d="M156 200 Q176 196 182 210 Q184 226 170 232 Q158 232 156 220 Z"/>
        <path class="bm-holo-lines" d="M126 240 Q138 234 150 240 Q162 246 174 240 Q178 250 168 254 Q156 258 144 254 Q130 252 126 262 Q130 272 144 268 Q158 264 172 268 Q178 276 166 282 Q152 286 138 282 Q126 280 124 270"/>
        <path class="bm-holo-lines" d="M120 244 L120 284 Q150 296 180 284 L180 244"/>
        <path class="bm-holo-lines" d="M134 28 Q140 22 146 28 Q150 24 154 28 Q160 22 166 28 M132 34 Q138 30 144 35 Q150 32 156 35 Q162 30 168 34 M146 42 Q150 52 154 42"/>
        <path class="bm-holo-lines" d="M150 100 L150 122 M150 122 L138 140 M150 122 L162 140
                 M138 140 L124 152 M138 140 L128 164 M138 140 L136 174 M124 152 L114 150 M124 152 L116 162
                 M162 140 L176 152 M162 140 L172 164 M162 140 L164 174 M176 152 L186 150 M176 152 L184 162"/>
        <path class="bm-holo-lines" d="M166 148 Q168 130 154 126 Q146 126 146 136 L146 198 Q148 222 150 242 M154 130 L154 196"/>
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

    {{-- Scan line, clipped to the silhouette. --}}
    <g class="bm-holo-fx" clip-path="url(#bm-holo-body-clip)" aria-hidden="true" pointer-events="none">
        <g class="bm-scan">
            <rect x="30" y="-22" width="240" height="44" fill="url(#bm-holo-scan)"/>
            <rect class="bm-scan-edge" x="30" y="-0.7" width="240" height="1.4"/>
        </g>
    </g>

    {{-- Status markers are drawn here by bodymap.js --}}
    <g data-bodymap-markers></g>
</svg>
