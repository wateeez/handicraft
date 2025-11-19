<?php
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        self::start();
        session_unset();
        session_destroy();
    }
    
    public static function getSessionId() {
        self::start();
        return session_id();
    }
    
    public static function setFlash($key, $message) {
        self::set('flash_' . $key, $message);
    }
    
    public static function getFlash($key) {
        self::start();
        $message = self::get('flash_' . $key);
        self::remove('flash_' . $key);
        return $message;
    }
    
    public static function isLoggedIn() {
        return self::has('admin_id');
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}
