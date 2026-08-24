<?php
// Application Configuration
define('APP_NAME', 'E-Find and Soft Solutions');
define('APP_URL', 'http://localhost/e_find');
define('APP_ENV', 'development');
define('APP_DEBUG', true);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Time Zone
date_default_timezone_set('Africa/Kampala');

// File Upload Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Payment Configuration
define('FLUTTERWAVE_PUBLIC_KEY', 'your_public_key');
define('FLUTTERWAVE_SECRET_KEY', 'your_secret_key');
define('FLUTTERWAVE_ENCRYPTION_KEY', 'your_encryption_key');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');