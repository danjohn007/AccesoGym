<?php
/**
 * AccessGYM Configuration Template
 * Copy this file to config.php and configure your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'fix360_accesogym');
define('DB_USER', 'fix360_accesogym');
define('DB_PASS', 'Danjohn007!');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'AccessGYM');

// Auto-detect APP_URL if not in CLI mode
if (php_sapi_name() !== 'cli') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . '://' . $host . ($scriptName !== '/' ? $scriptName : '');
    define('APP_URL', rtrim($baseUrl, '/'));
} else {
    define('APP_URL', 'http://localhost');
}

define('APP_TIMEZONE', 'America/Mexico_City');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');

// File Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Email Configuration (SMTP)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'noreply@accessgym.com');
define('SMTP_FROM_NAME', 'AccessGYM');

// Shelly Cloud API
define('SHELLY_ENABLED', true);
define('SHELLY_SERVER_URL', 'https://shelly-208-eu.shelly.cloud');
define('SHELLY_AUTH_TOKEN', 'MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74');
define('SHELLY_API_URL', 'https://shelly-208-eu.shelly.cloud/device/status'); // Legacy support
define('SHELLY_API_KEY', 'MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74'); // Legacy support

// WhatsApp Business API
define('WHATSAPP_ENABLED', false);
define('WHATSAPP_PHONE_ID', '');
define('WHATSAPP_TOKEN', '');
define('WHATSAPP_VERIFY_TOKEN', '');
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v17.0/');

// Payment Gateways
define('STRIPE_ENABLED', false);
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');

define('MERCADOPAGO_ENABLED', false);
define('MERCADOPAGO_PUBLIC_KEY', '');
define('MERCADOPAGO_ACCESS_TOKEN', '');

define('CONEKTA_ENABLED', false);
define('CONEKTA_PUBLIC_KEY', '');
define('CONEKTA_PRIVATE_KEY', '');

// QR Code Generation
define('QR_API_URL', 'https://api.qrserver.com/v1/create-qr-code/');
define('QR_SIZE', '200x200');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEWS_PATH', APP_PATH . '/views');
define('LOGS_PATH', BASE_PATH . '/logs');

// Error Reporting
// IMPORTANT: Set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 0); // Change to 0 for production
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// Timezone
date_default_timezone_set(APP_TIMEZONE);
