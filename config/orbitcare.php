<?php

return [
    // Demo tools are intentionally opt-in and are never available in production.
    'demo_mode' => env('ORBITCARE_DEMO_MODE', false),
    'demo_database' => env('ORBITCARE_DEMO_DATABASE', 'orbitcare'),
    'demo_anchor_date' => env('ORBITCARE_DEMO_ANCHOR_DATE'),
    // Displays limited, fictional judge credentials on the login page. This does
    // not enable reset or simulation controls.
    'judge_demo' => env('ORBITCARE_JUDGE_DEMO', false),
];
