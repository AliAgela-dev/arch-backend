<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Test Case Binding
|--------------------------------------------------------------------------
|
| Bind Pest's test closures to Laravel's base TestCase so that helpers like
| $this->get(), $this->post(), etc. are available in Feature tests.
|
*/

uses(TestCase::class)->in('Feature');
