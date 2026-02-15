<?php
/**
 * Session Management
 * Campus Room Allocation System
 * 
 * Handles user sessions, authentication checks, and security
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for security
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1);  // Only use cookies for sessions
    ini_set('session.cookie_secure', 0);     // Set to 1 if using HTTPS
    
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is student
 * 
 * @return bool True if user is student
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'student_id' => $_SESSION['student_id'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'year_level' => $_SESSION['year_level'] ?? null,
        'program' => $_SESSION['program'] ?? null
    ];
}

/**
 * Get full name of current user
 * 
 * @return string Full name or empty string
 */
function getCurrentUserName() {
    if (!isLoggedIn()) {
        return '';
    }
    return ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
}

/**
 * Set user session after successful login
 * 
 * @param array $user User data from database
 */
function setUserSession($user) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_role'] = $user['user_role'];
    $_SESSION['year_level'] = $user['year_level'];
    $_SESSION['program'] = $user['program'];
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID after login for security
    session_regenerate_id(true);
}

/**
 * Destroy user session (logout)
 */
function destroyUserSession() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Require login - redirect to login page if not logged in
 * 
 * @param string $redirect_to URL to redirect to after login
 */
function requireLogin($redirect_to = '') {
    if (!isLoggedIn()) {
        if (empty($redirect_to)) {
            $redirect_to = $_SERVER['REQUEST_URI'];
        }
        header('Location: /csc433/auth/login.php?redirect=' . urlencode($redirect_to));
        exit();
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /csc433/pages/dashboard.php?error=access_denied');
        exit();
    }
}

/**
 * Require student - redirect if not student
 */
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: /csc433/pages/dashboard.php?error=access_denied');
        exit();
    }
}

/**
 * Check if session is expired (inactive for 30 minutes)
 * 
 * @return bool True if session expired
 */
function isSessionExpired() {
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive > 1800) { // 30 minutes
            return true;
        }
    }
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Set flash message for next page load
 * 
 * @param string $message Message text
 * @param string $type success|error|warning|info
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 * 
 * @return array|null ['message' => '...', 'type' => '...'] or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 * 
 * @return string HTML for flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        $class = $alertClass[$flash['type']] ?? 'alert-info';
        
        return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($flash['message']) .
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
    }
    return '';
}

/**
 * Prevent CSRF attacks - generate token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 * 
 * @return string HTML input field
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Check for session expiration on every page load
if (isLoggedIn() && isSessionExpired()) {
    destroyUserSession();
    header('Location: /csc433/auth/login.php?error=session_expired');
    exit();
}

?>