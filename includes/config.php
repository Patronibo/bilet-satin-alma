<?php
/**
 * Güvenlik ve Konfigürasyon Ayarları
 */

// Production modunda hata göstermeyi kapat
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Hataları log dosyasına yaz
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Güvenlik başlıkları
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Content Security Policy (XSS koruması)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");

// HSTS (HTTPS zorlama - production için)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Timezone ayarla
date_default_timezone_set('Europe/Istanbul');

// Session timeout süresi (saniye)
define('SESSION_TIMEOUT', 3600); // 1 saat

// Rate limiting ayarları
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_TIMEFRAME', 300); // 5 dakika

// Maximum file upload size
// Dosya yükleme olmadığı için aslında file upload zaafiyeti yapmanın şuanlık bir anlamı yoktur.
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

