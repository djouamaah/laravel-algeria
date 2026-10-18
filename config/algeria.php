<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Territorial division
    |--------------------------------------------------------------------------
    |
    | 69 : the 2026 division (law 26-06 of 4 April 2026, decree 26-206), 69 wilayas.
    | 58 : the 2019 division (law 19-12), 58 wilayas.
    |
    | Commune ids are identical in both divisions, so switching only moves
    | communes between wilayas/dairas; it never renumbers them.
    |
    */

    'division' => (int) env('ALGERIA_DIVISION', 69),

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    */

    'tables' => [
        'wilayas' => 'wilayas',
        'dairas' => 'dairas',
        'communes' => 'communes',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON API (for cascading wilaya -> daira -> commune selects)
    |--------------------------------------------------------------------------
    |
    | GET {prefix}/wilayas
    | GET {prefix}/wilayas/{wilaya}
    | GET {prefix}/wilayas/{wilaya}/dairas
    | GET {prefix}/wilayas/{wilaya}/communes
    | GET {prefix}/dairas/{daira}/communes
    |
    */

    'routes' => [
        'enabled' => (bool) env('ALGERIA_ROUTES', false),
        'prefix' => 'api/algeria',
        'middleware' => ['api'],
        'name' => 'algeria.',
    ],

];
