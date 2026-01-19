<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Borrowing System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for the Borrowing (Loan) System module.
    | All durations are in days. These values can be overridden via .env file.
    |
    */

    /**
     * Default borrow duration in days.
     * This is used when approving a borrowing request without specifying a custom duration.
     */
    'default_duration_days' => env('BORROWING_DEFAULT_DURATION', 14),

    /**
     * Maximum allowed borrow duration in days.
     * Admins cannot set a duration longer than this value.
     */
    'max_duration_days' => env('BORROWING_MAX_DURATION', 30),

    /**
     * Minimum allowed borrow duration in days.
     * Admins cannot set a duration shorter than this value.
     */
    'min_duration_days' => env('BORROWING_MIN_DURATION', 1),

    /**
     * Enable or disable automatic overdue status detection.
     * When enabled, the system will automatically mark borrowings as overdue.
     */
    'overdue_check_enabled' => env('BORROWING_OVERDUE_CHECK', true),

    /**
     * Auto-cancel pending requests after this many days.
     * Pending requests older than this will be automatically cancelled.
     * Set to null to disable auto-cancellation.
     */
    'auto_cancel_pending_after_days' => env('BORROWING_AUTO_CANCEL_DAYS', 7),
];
