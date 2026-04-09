<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destination
    |--------------------------------------------------------------------------
    |
    | The directory where the static site should be generated.
    |
    */

    'destination' => base_path('static'),

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | The specific URLs that should be generated. If left empty, all
    | entries, terms, and other content will be included.
    |
    */

    'urls' => [
        '/',
        '/first-ever-story',
        '/a-branch-on-paragliding',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rewrite URLs
    |--------------------------------------------------------------------------
    |
    | Whether URLs should be rewritten to point to the static files.
    |
    */

    'rewrite_urls' => true,

    /*
    |--------------------------------------------------------------------------
    | Relative URLs
    |--------------------------------------------------------------------------
    |
    | Whether the generated URLs should be relative. This is useful if
    | you plan on opening the site from a file system.
    |
    */

    'use_relative_urls' => true,

    /*
    |--------------------------------------------------------------------------
    | Be Very Quiet
    |--------------------------------------------------------------------------
    |
    | Whether to suppress all output from the generation process.
    |
    */

    'be_very_quiet' => false,

];
