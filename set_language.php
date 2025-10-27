<?php
// Super simple language switcher - just set session and redirect back
require_once __DIR__ . '/includes/session.php';

$lang = $_GET['lang'] ?? 'en';

// Validate and set language
if (in_array($lang, ['en', 'ar'])) {
    $_SESSION['ui_lang'] = $lang;
    
    // Debug: uncomment to see if this is being reached
    // file_put_contents(__DIR__ . '/debug_lang.txt', date('Y-m-d H:i:s') . " - Set language to: $lang\n", FILE_APPEND);
}

// Redirect back to referrer or home
$redirect = $_SERVER['HTTP_REFERER'] ?? 'home.php';
header('Location: ' . $redirect);
exit;
?>

