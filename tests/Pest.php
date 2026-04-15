<?php

/**
 * Global Pest configuration.
 *
 * - Binds the Laravel TestCase to all tests in the tests/ directory.
 * - Feature tests use RefreshDatabase by default via their own uses() call.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Wire up the Laravel application to every test file.
 * Without this, `app()`, factories, and the DB container are unavailable.
 */
uses(TestCase::class)->in('Feature', 'Unit');
