<?php

return [
    'name' => 'HeaderFooter',

    /* FR-HF-01: variants per region; the active theme (C-06) will offer its own. */
    'variants' => [
        'topbar' => ['default'],
        'header' => ['classic', 'centered', 'minimal'],
        'footer' => ['columns', 'compact'],
    ],
];
