<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * Base controller for all Admin API controllers.
 *
 * Provides standardized response methods and common functionality.
 */
abstract class AdminController extends Controller
{
    use ApiResponse;
}
