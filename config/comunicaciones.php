<?php

$maxLeadDays = max(1, (int) env('COMMUNICATIONS_MAX_LEAD_DAYS', 30));
$maxDailyLimit = max(1, (int) env('COMMUNICATIONS_MAX_DAILY_LIMIT', 5));

return [
    // An operator setting alone can never enable external traffic in a new environment.
    'send_enabled' => env('COMMUNICATIONS_SEND_ENABLED', false),
    'test_recipient' => env('COMMUNICATIONS_TEST_RECIPIENT'),
    'whatsapp_test_enabled' => env('COMMUNICATIONS_WHATSAPP_TEST_ENABLED', false),
    'whatsapp_test_recipient' => env('COMMUNICATIONS_WHATSAPP_TEST_RECIPIENT'),
    'default_lead_days' => min($maxLeadDays, max(1, (int) env('COMMUNICATIONS_DEFAULT_LEAD_DAYS', 3))),
    'max_lead_days' => $maxLeadDays,
    'default_timezone' => env('COMMUNICATIONS_DEFAULT_TIMEZONE', 'America/Mexico_City'),
    'default_window_start' => env('COMMUNICATIONS_DEFAULT_WINDOW_START', '09:00'),
    'default_window_end' => env('COMMUNICATIONS_DEFAULT_WINDOW_END', '20:00'),
    'default_daily_limit' => min($maxDailyLimit, max(1, (int) env('COMMUNICATIONS_DEFAULT_DAILY_LIMIT', 1))),
    'max_daily_limit' => $maxDailyLimit,
    'payment_event_validity_hours' => max(1, (int) env('COMMUNICATIONS_PAYMENT_VALIDITY_HOURS', 24)),
    'queue_stale_minutes' => max(1, (int) env('COMMUNICATIONS_QUEUE_STALE_MINUTES', 15)),
    'sending_uncertain_minutes' => max(1, (int) env('COMMUNICATIONS_SENDING_UNCERTAIN_MINUTES', 5)),
    'due_schedule_minutes' => min(60, max(1, (int) env('COMMUNICATIONS_DUE_SCHEDULE_MINUTES', 60))),
    'dispatch_schedule_minutes' => min(60, max(1, (int) env('COMMUNICATIONS_DISPATCH_SCHEDULE_MINUTES', 5))),
];
