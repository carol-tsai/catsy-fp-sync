<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Simple login function - you should enhance this with proper security
function login($username, $password) {
    // Replace with your actual authentication logic
    $validUsername = 'admin';
    $validPassword = 'your_secure_password'; // Store hashed password in production
    
    if ($username === $validUsername && password_verify($password, password_hash($validPassword, PASSWORD_DEFAULT))) {
        $_SESSION['loggedin'] = true;
        return true;
    }
    
    return false;
}