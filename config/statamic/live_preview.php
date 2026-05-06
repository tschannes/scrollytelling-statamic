<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Devices
    |--------------------------------------------------------------------------
    |
    | Live Preview displays a device selector for you to preview the page
    | in predefined sizes. You are free to add or edit these presets.
    |
    */

    'devices' => [
        // "Full" uses a width that fits the CP preview panel without clipping.
        // For a true full-width preview, use the "Pop Out" button in Live Preview.
        'Full'   => ['width' => 1280, 'height' => 800],
        'Tablet' => ['width' => 768,  'height' => 1024],
        'Mobile' => ['width' => 390,  'height' => 844],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Inputs
    |--------------------------------------------------------------------------
    |
    | Additional fields may be added to the Live Preview header bar. You
    | may define a list of Vue components to be injected. Their values
    | will be added to the cascade on the front-end for you to use.
    |
    */

    'inputs' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Reload Javascript Modules
    |--------------------------------------------------------------------------
    |
    | To force a reload, Live Preview appends a timestamp to the URL on
    | script tags of type 'module'. You may disable this behavior here.
    |
    */

    'force_reload_js_modules' => true,

    /*
    |--------------------------------------------------------------------------
    | Hot Reload Contents
    |--------------------------------------------------------------------------
    |
    | Should the Live Preview embed be hot-reloaded when the content changes?
    | Only applies when "Refresh" is disabled on the live preview target.
    |
    */

    'hot_reload_contents' => true,

];