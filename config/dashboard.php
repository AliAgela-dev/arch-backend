<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Limit
    |--------------------------------------------------------------------------
    |
    | The maximum storage capacity in bytes. Default is 2TB.
    | This is used to calculate storage percentage on the dashboard.
    |
    */
    'storage_limit_bytes' => env('DASHBOARD_STORAGE_LIMIT_BYTES', 2 * 1024 * 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Storage Warning Threshold
    |--------------------------------------------------------------------------
    |
    | The percentage at which a storage warning is displayed.
    | Default is 60%.
    |
    */
    'storage_warning_threshold' => env('DASHBOARD_STORAGE_WARNING_THRESHOLD', 60),
];
