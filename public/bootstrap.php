<?php
/**
 * Bootstrap file
 * Loads configuration and initializes the application
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// Load helpers
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Initialize authentication
Auth::init();
