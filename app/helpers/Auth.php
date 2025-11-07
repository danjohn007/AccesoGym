<?php
/**
 * Auth Class
 * Handles authentication and authorization
 */

class Auth {
    /**
     * Start session if not started
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            session_start();
        }
    }
    
    /**
     * Login user
     */
    public static function login($user) {
        self::init();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['rol'];
        $_SESSION['sucursal_id'] = $user['sucursal_id'];
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::init();
        session_destroy();
        session_unset();
    }
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        self::init();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Get current user
     */
    public static function user() {
        self::init();
        
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'rol' => $_SESSION['user_role'],
            'sucursal_id' => $_SESSION['sucursal_id']
        ];
    }
    
    /**
     * Get user ID
     */
    public static function id() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get user role
     */
    public static function role() {
        self::init();
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Get user branch
     */
    public static function sucursalId() {
        self::init();
        return $_SESSION['sucursal_id'] ?? null;
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole($role) {
        return self::role() === $role;
    }
    
    /**
     * Check if user is superadmin
     */
    public static function isSuperadmin() {
        return self::role() === 'superadmin';
    }
    
    /**
     * Check if user is admin or superadmin
     */
    public static function isAdmin() {
        return in_array(self::role(), ['superadmin', 'admin']);
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($roles) {
        self::requireAuth();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array(self::role(), $roles)) {
            header('HTTP/1.1 403 Forbidden');
            die('Acceso no autorizado');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        self::init();
        
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        self::init();
        
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
