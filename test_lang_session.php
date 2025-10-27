<?php
require_once __DIR__ . '/includes/session.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='" . get_language() . "' dir='" . get_direction() . "'>\n";
echo "<head><title>Language Session Test</title></head>\n";
echo "<body style='font-family: sans-serif; padding: 20px;'>\n";
echo "<h1>Language Session Test</h1>\n";
echo "<p><strong>Current Language:</strong> " . get_language() . "</p>\n";
echo "<p><strong>Current Direction:</strong> " . get_direction() . "</p>\n";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>\n";
echo "<p><strong>All Session Data:</strong></p>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

echo "<hr>\n";
echo "<h2>Test Language Switch</h2>\n";
echo "<form method='POST'>\n";
echo "  <button name='lang' value='en'>Switch to English</button>\n";
echo "  <button name='lang' value='ar'>Switch to Arabic</button>\n";
echo "</form>\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $newLang = $_POST['lang'];
    set_language($newLang);
    echo "<p style='color: green;'><strong>Language switched to: " . $newLang . "</strong></p>\n";
    echo "<p>Reload page to see changes...</p>\n";
    echo "<meta http-equiv='refresh' content='1'>\n";
}

echo "</body>\n";
echo "</html>\n";
?>

