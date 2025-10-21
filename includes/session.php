<?php
// Centralized session bootstrap
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30, // 30 days
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Helpers
function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_guest() {
    if (current_user()) {
        header('Location: home.php');
        exit;
    }
}

function require_auth() {
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}
?>


