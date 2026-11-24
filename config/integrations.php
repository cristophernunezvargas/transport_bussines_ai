<?php

return [

    /*
    |--------------------------------------------------------------------------
    | n8n
    |--------------------------------------------------------------------------
    |
    | n8n pushes data into this app over the token-protected `/api/integrations`
    | routes (see routes/api.php). The token is a long random secret that lives
    | only in each environment's .env and in n8n's credential store; with no
    | token configured every integration request is refused. Records created
    | through the integration are attributed (`created_by`) to `user_email`,
    | which should be a dedicated login created on the Users screen.
    |
    */

    'n8n' => [
        'token' => env('N8N_API_TOKEN'),
        'user_email' => env('N8N_USER_EMAIL'),
    ],

];
