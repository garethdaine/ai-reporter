<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third-Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third-party services such
    | as Mailgun, Postmark, AWS and—now—OpenAI.  This file provides a standard
    | location for this type of information, allowing packages to have a
    | conventional file to locate the various service credentials.
    |
    */

    // … existing services (mailgun, postmark, etc.)

    'openai' => [
        /*
        |--------------------------------------------------------------
        | API Key
        |--------------------------------------------------------------
        | Your secret key from https://platform.openai.com/account/api-keys
        | Keep this value in your .env; do not commit secrets.
        */
        'key' => env('OPENAI_API_KEY'),

        /*
        |--------------------------------------------------------------
        | Optional: Organisation / Team ID
        |--------------------------------------------------------------
        | Useful if your account belongs to multiple orgs.
        | Leave null to let OpenAI infer the default.
        */
        'organization' => env('OPENAI_ORGANIZATION'),

        /*
        |--------------------------------------------------------------
        | Optional: Custom Base URL
        |--------------------------------------------------------------
        | If you’re routing through a proxy or using Azure OpenAI,
        | override the base URL here.
        */
        // 'base_uri' => env('OPENAI_BASE_URI'),

        /*
        |--------------------------------------------------------------
        | Default Model
        |--------------------------------------------------------------
        | AI Reporter passes the model when it calls OpenAI, but you
        | can keep a project-wide default here if you wish.
        */
        // 'model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
    ],

];
