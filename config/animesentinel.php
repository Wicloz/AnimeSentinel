<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MAL user username
    |--------------------------------------------------------------------------
    |
    | This is the username which will be used for MAL api requests.
    |
    */

    'mal_username' => env('MAL_USER', ''),

    /*
    |--------------------------------------------------------------------------
    | MAL user password
    |--------------------------------------------------------------------------
    |
    | This is the password which will be used for MAL api requests.
    |
    */

    'mal_password' => env('MAL_PASS', ''),

    /*
    |--------------------------------------------------------------------------
    | KissAnime username cookie
    |--------------------------------------------------------------------------
    |
    | This is the username cookie which will be used for KissAnime page downloads.
    |
    */

    'kissanime_username_cookie' => env('KISSANIME_USER', ''),

    /*
    |--------------------------------------------------------------------------
    | KissAnime password cookie
    |--------------------------------------------------------------------------
    |
    | This is the password cookie which will be used for KissAnime page downloads.
    |
    */

    'kissanime_password_cookie' => env('KISSANIME_PASS', ''),

];
