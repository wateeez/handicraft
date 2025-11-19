<?php

// Session helper class for backward compatibility with legacy code
class Session {
    public static function set($key, $value) {
        session([$key => $value]);
    }
    
    public static function get($key, $default = null) {
        return session($key, $default);
    }
    
    public static function has($key) {
        return session()->has($key);
    }
    
    public static function remove($key) {
        session()->forget($key);
    }
    
    public static function setFlash($key, $value) {
        session()->flash($key, $value);
    }
    
    public static function getFlash($key) {
        return session($key);
    }
    
    public static function getSessionId() {
        return session()->getId();
    }
    
    public static function destroy() {
        session()->flush();
    }
    
    public static function requireLogin() {
        if (!session()->has('admin_id')) {
            redirect(route('admin.login'))->send();
            exit;
        }
    }
}
