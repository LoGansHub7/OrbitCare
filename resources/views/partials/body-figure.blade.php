{{--
    Hand-authored SVG human figure for the Body Map. Front view only in this stage
    (back view added in Stage 3). Each selectable region is a <g> with:
    - id="{svg_id}"        matches body_regions.svg_id
    - data-region="{slug}" matches body_regions.slug
    - tabindex="0" role="button" for keyboard access
    Status is applied purely via CSS classes/data-status from JS; this partial
    only defines shapes and ids, no inline status logic.
--}}
<svg class="oc-body-figure" viewBox="0 0 400 900" role="img" aria-label="Human body diagram, front view" xmlns="http://www.w3.org/2000/svg" data-figure-view="front">
    <defs>
        <radialGradient id="oc-figure-skin-grad" cx="50%" cy="35%" r="75%">
            <stop offset="0%" stop-color="var(--oc-figure-skin-light)" />
            <stop offset="100%" stop-color="var(--oc-figure-skin-dark)" />
        </radialGradient>
    </defs>

    {{-- Skin layer: base silhouette, shown by default --}}
    <g class="oc-figure-layer" data-layer="skin">
        <path class="oc-figure-skin" d="M200,60
            c28,0 48,22 48,50 c0,16 -6,28 -14,38
            c22,10 38,30 42,56 l10,70 c2,16 -4,30 -16,36
            l-6,120 c10,8 16,20 16,34 l4,170 c0,14 -10,24 -24,24
            l-30,0 c-10,0 -18,-8 -18,-18 l-4,-130
            c0,-6 -4,-10 -8,-10 c-4,0 -8,4 -8,10 l-4,130
            c0,10 -8,18 -18,18 l-30,0 c-14,0 -24,-10 -24,-24
            l4,-170 c0,-14 6,-26 16,-34 l-6,-120
            c-12,-6 -18,-20 -16,-36 l10,-70 c4,-26 20,-46 42,-56
            c-8,-10 -14,-22 -14,-38 c0,-28 20,-50 48,-50 z" />
    </g>

    {{-- Muscle layer: shown by default, subtle definition over the torso/limbs --}}
    <g class="oc-figure-layer" data-layer="muscle" opacity="0">
        <path class="oc-figure-muscle-tone" d="M164,210 c14,-8 58,-8 72,0 l6,90 c-14,10 -70,10 -84,0 z" />
        <path class="oc-figure-muscle-tone" d="M120,230 c6,30 2,70 -6,100 M280,230 c-6,30 -2,70 6,100" fill="none" />
    </g>

    {{-- Skeleton layer: hidden by default --}}
    <g class="oc-figure-layer" data-layer="skeleton" opacity="0">
        <line class="oc-figure-bone" x1="200" y1="150" x2="200" y2="640" />
        <line class="oc-figure-bone" x1="160" y1="300" x2="160" y2="640" />
        <line class="oc-figure-bone" x1="240" y1="300" x2="240" y2="640" />
    </g>

    {{-- Organs layer: shown by default --}}
    <g class="oc-figure-layer" data-layer="organs" opacity="1">
        {{-- Head / brain --}}
        <g id="region-head" data-region="head-brain" class="oc-region" tabindex="0" role="button" aria-label="Head and brain">
            <circle class="oc-region-shape" cx="200" cy="108" r="46" />
        </g>

        {{-- Eyes --}}
        <g id="region-eyes" data-region="eyes" class="oc-region" tabindex="0" role="button" aria-label="Eyes">
            <circle class="oc-region-shape oc-region-shape--small" cx="186" cy="102" r="5" />
            <circle class="oc-region-shape oc-region-shape--small" cx="214" cy="102" r="5" />
        </g>

        {{-- Lungs --}}
        <g id="region-lungs" data-region="lungs" class="oc-region oc-region--breathing" tabindex="0" role="button" aria-label="Lungs">
            <path class="oc-region-shape" d="M170,230 c-22,4 -34,30 -30,62 c3,24 16,38 32,36 c6,-26 4,-70 -2,-98 z" />
            <path class="oc-region-shape" d="M230,230 c22,4 34,30 30,62 c-3,24 -16,38 -32,36 c-6,-26 -4,-70 2,-98 z" />
        </g>

        {{-- Heart --}}
        <g id="region-heart" data-region="heart" class="oc-region oc-region--heartbeat" tabindex="0" role="button" aria-label="Heart">
            <path class="oc-region-shape" d="M200,250 c-10,-14 -34,-14 -34,8 c0,18 20,32 34,44 c14,-12 34,-26 34,-44 c0,-22 -24,-22 -34,-8 z" />
        </g>

        {{-- Digestive --}}
        <g id="region-digestive" data-region="digestive" class="oc-region" tabindex="0" role="button" aria-label="Digestive system">
            <ellipse class="oc-region-shape" cx="200" cy="340" rx="34" ry="40" />
        </g>

        {{-- Spine --}}
        <g id="region-spine" data-region="spine" class="oc-region" tabindex="0" role="button" aria-label="Spine">
            <line class="oc-region-shape oc-region-shape--line" x1="200" y1="160" x2="200" y2="470" />
        </g>

        {{-- Upper-body muscles --}}
        <g id="region-upper-muscles" data-region="upper-body-muscles" class="oc-region" tabindex="0" role="button" aria-label="Upper body muscles">
            <rect class="oc-region-shape" x="110" y="200" width="36" height="110" rx="14" />
            <rect class="oc-region-shape" x="254" y="200" width="36" height="110" rx="14" />
        </g>

        {{-- Lower-body muscles --}}
        <g id="region-lower-muscles" data-region="lower-body-muscles" class="oc-region" tabindex="0" role="button" aria-label="Lower body muscles">
            <rect class="oc-region-shape" x="156" y="540" width="38" height="140" rx="16" />
            <rect class="oc-region-shape" x="206" y="540" width="38" height="140" rx="16" />
        </g>

        {{-- Long bones (hips/legs) --}}
        <g id="region-long-bones" data-region="long-bones" class="oc-region" tabindex="0" role="button" aria-label="Long bones, hips and legs">
            <rect class="oc-region-shape" x="160" y="680" width="30" height="160" rx="12" />
            <rect class="oc-region-shape" x="210" y="680" width="30" height="160" rx="12" />
        </g>

        {{-- Skin (surface, separate clickable region from the base silhouette) --}}
        <g id="region-skin" data-region="skin" class="oc-region" tabindex="0" role="button" aria-label="Skin">
            <rect class="oc-region-shape oc-region-shape--outline" x="150" y="470" width="100" height="60" rx="10" />
        </g>

        {{-- Whole body / radiation --}}
        <g id="region-whole-body" data-region="whole-body-radiation" class="oc-region" tabindex="0" role="button" aria-label="Whole body radiation exposure">
            <rect class="oc-region-shape oc-region-shape--outline oc-region-shape--wide" x="110" y="60" width="180" height="780" rx="60" />
        </g>
    </g>
</svg>
