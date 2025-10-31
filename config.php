<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// PayGate Configuration - REPLACE WITH YOUR ACTUAL CREDENTIALS
define('PAYGATE_ID', '10011072130'); // REPLACE WITH YOUR PAYGATE ID
define('PAYGATE_ENCRYPTION_KEY', 'secret'); // REPLACE WITH YOUR ENCRYPTION KEY

// PayGate URLs
define('PAYGATE_SANDBOX', true); // Set to false for production
define('PAYGATE_URL', PAYGATE_SANDBOX ? 
    'https://secure.paygate.co.za/payweb3/process.trans' : 
    'https://secure.paygate.co.za/payweb3/process.trans');

// Site Configuration
define('SITE_URL', 'https://citywingshuttles.com'); // REPLACE WITH YOUR DOMAIN
define('CURRENCY', 'ZAR');
define('CURRENCY_SYMBOL', 'N$');

// Merchant Bank Details (for display only)
define('BANK_NAME', 'FNB Namibia');
define('ACCOUNT_NAME', 'CITYWING TRANSFERS AND SAFARIS CC');
define('ACCOUNT_TYPE', 'GOLD BUSINESS ACCOUNT');
define('ACCOUNT_NUMBER', '64284098885');

// Admin Configuration
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('Citywing2025', PASSWORD_DEFAULT)); // REPLACE WITH SECURE PASSWORD

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>