<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DevScore points
    |--------------------------------------------------------------------------
    |
    | Points awarded for each action (cahier des charges, section 15).
    | Keys match the App\Enums\PointAction values.
    |
    */

    'points' => [
        'profile_completed' => 20,
        'project_created' => 50,
        'resource_published' => 30,
        'collaboration' => 40,
        'resource_liked' => 5,
        'project_completed' => 100,
    ],

];
