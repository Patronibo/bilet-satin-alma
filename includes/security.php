<?php
/**
 * Güvenlik Helper Fonksiyonları
 * XSS, CSRF, Input Validation için merkezi güvenlik fonksiyonları
 */

/**
 * XSS koruması için güvenli output
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF token oluştur
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrulama
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting için basit kontrol
 */
function check_rate_limit($action, $max_attempts = 5, $timeframe = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Zaman aşımı kontrolü
    if (($now - $data['start']) > $timeframe) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    // Limit kontrolü
    if ($data['count'] >= $max_attempts) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Input sanitization
 */
function sanitize_input($data, $type = 'string') {
    $data = trim((string)$data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate input
 */
function validate_input($data, $type, $required = true) {
    if ($required && empty($data)) {
        return false;
    }
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) !== false;
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL) !== false;
        case 'tc':
            return preg_match('/^\d{11}$/', $data) === 1;
        case 'phone':
            return preg_match('/^\d{10,11}$/', $data) === 1;
        case 'card':
            return preg_match('/^\d{16}$/', preg_replace('/\s+/', '', $data)) === 1;
        case 'cvv':
            return preg_match('/^\d{3,4}$/', $data) === 1;
        default:
            return !empty($data);
    }
}

/**
 * Secure session başlatma
 */
function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
               || (($_SERVER['SERVER_PORT'] ?? '') === '443')
               || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
    
    // Session hijacking koruması
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    } else {
        if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_unset();
            session_destroy();
            session_start();
        }
    }
    
    // Session regeneration
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Her 5 dakikada bir
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Yetkilendirme kontrolü
 */
function check_auth($required_role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        secure_session_start();
    }
    
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    
    if ($required_role && ($_SESSION['role'] ?? '') !== $required_role) {
        return false;
    }
    
    return true;
}

/**
 * SQL Injection koruması için PDO prepared statement helper
 */
function safe_query($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Error response helper (güvenli hata mesajları)
 */
function secure_error_response($message = 'Bir hata oluştu', $redirect = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['error_message'] = $message;
    
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Başarı response helper
 */
function success_response($message, $redirect = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['success_message'] = $message;
    
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    }
}

