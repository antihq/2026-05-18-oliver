<?php

return [

    'github_org' => env('GITHUB_ORG', 'antihq'),

    'cache_ttl' => env('GITHUB_CACHE_TTL', 21600),

    'repos' => [
        ['title' => 'Mayfly', 'repo' => '2026-05-18-mayfly'],
        ['title' => 'Tuner', 'repo' => '2026-05-07-tuner'],
        ['title' => 'Glace', 'repo' => '2026-05-04-glace'],
        ['title' => 'Vault', 'repo' => '2026-05-14-vault'],
    ],

];
