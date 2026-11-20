<?php

return [
    // An operator setting alone can never enable external traffic in a new environment.
    'send_enabled' => env('COMMUNICATIONS_SEND_ENABLED', false),
    'test_recipient' => env('COMMUNICATIONS_TEST_RECIPIENT'),
];
