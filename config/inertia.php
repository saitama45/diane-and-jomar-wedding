<?php

/*
 * Only what differs from the package defaults (vendor/inertiajs/inertia-laravel/config/inertia.php).
 * Laravel merges package config one top-level key at a time, so `pages` lists all of its options.
 *
 * This app keeps its pages in resources/js/Pages. The package default, "js/pages", only matches on
 * case-insensitive Windows, so Inertia's page-exists checks failed on Linux (GitHub Actions).
 */
return [

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],

    ],

];
