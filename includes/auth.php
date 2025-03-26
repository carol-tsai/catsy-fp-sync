<?php
require_once __DIR__ . '/../config/auth.php';

function checkLogin() {
    session_start();
    
    // Check brute force protection
    if (isset($_SESSION['login_attempts']) && 
        $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && 
        time() - $_SESSION['last_attempt_time'] < LOGIN_TIMEOUT) {
        die('Too many login attempts. Please try again later.');
    }
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function login($username, $password) {
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['login_attempts'] = 0; // Reset on success
        return true;
    }
    
    // Track failed attempts
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_attempt_time'] = time();
    
    return false;
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}