<?php

declare(strict_types=1);

return [
    'sk' => env('STRIPE_SK'),
    'pk' => env('STRIPE_PK'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
