<?php
require_once __DIR__ . '/includes/session.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='" . get_language() . "' dir='" . get_direction() . "'>\n";
echo "<head>\n";
echo "  <meta charset='UTF-8'>\n";
echo "  <title>Session Test</title>\n";
echo "  <style>\n";
echo "    body { font-family: Arial; padding: 20px; background: #f5f5f5; }\n";
echo "    .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "    .success { background: #d4edda; border-left: 4px solid #28a745; }\n";
echo "    .error { background: #f8d7da; border-left: 4px solid #dc3545; }\n";
echo "    button { padding: 10px 20px; margin: 5px; cursor: pointer; font-size: 16px; }\n";
echo "    .rtl-test { border: 2px dashed #007bff; padding: 15px; margin: 10px 0; }\n";
echo "  </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🧪 Session & Language Test</h1>\n";

// Session Status
echo "<div class='box " . (session_status() === PHP_SESSION_ACTIVE ? "success" : "error") . "'>\n";
echo "  <h2>Session Status</h2>\n";
echo "  <p><strong>Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ INACTIVE") . "</p>\n";
echo "  <p><strong>Session ID:</strong> " . session_id() . "</p>\n";
echo "</div>\n";

// Language Settings
$lang = get_language();
$dir = get_direction();
echo "<div class='box " . ($lang ? "success" : "error") . "'>\n";
echo "  <h2>Language Settings</h2>\n";
echo "  <p><strong>\$_SESSION['ui_lang']:</strong> " . ($_SESSION['ui_lang'] ?? '❌ NOT SET') . "</p>\n";
echo "  <p><strong>get_language():</strong> " . $lang . "</p>\n";
echo "  <p><strong>get_direction():</strong> " . $dir . "</p>\n";
echo "  <p><strong>HTML lang attribute:</strong> <code>" . htmlspecialchars(get_language()) . "</code></p>\n";
echo "  <p><strong>HTML dir attribute:</strong> <code>" . htmlspecialchars(get_direction()) . "</code></p>\n";
echo "</div>\n";

// RTL Test
echo "<div class='box'>\n";
echo "  <h2>RTL Layout Test</h2>\n";
echo "  <div class='rtl-test'>\n";
echo "    <p>This is a test paragraph. In RTL mode, text should align to the right.</p>\n";
echo "    <p>هذا نص باللغة العربية. يجب أن يظهر من اليمين إلى اليسار.</p>\n";
echo "  </div>\n";
echo "  <p><strong>Current Direction:</strong> " . ($dir === 'rtl' ? '✅ RTL (Right-to-Left)' : '✅ LTR (Left-to-Right)') . "</p>\n";
echo "</div>\n";

// Language Switch Buttons
echo "<div class='box'>\n";
echo "  <h2>Switch Language</h2>\n";
echo "  <p>Click a button to switch language. Page will redirect back here.</p>\n";
echo "  <a href='set_language.php?lang=en'><button style='background: #007bff; color: white; border: none;'>🇺🇸 Switch to English</button></a>\n";
echo "  <a href='set_language.php?lang=ar'><button style='background: #28a745; color: white; border: none;'>🇸🇦 Switch to Arabic</button></a>\n";
echo "</div>\n";

// JavaScript Detection
echo "<div class='box'>\n";
echo "  <h2>JavaScript Detection</h2>\n";
echo "  <p><strong>document.documentElement.lang:</strong> <code id='jsLang'></code></p>\n";
echo "  <p><strong>document.documentElement.dir:</strong> <code id='jsDir'></code></p>\n";
echo "  <script>\n";
echo "    document.getElementById('jsLang').textContent = document.documentElement.getAttribute('lang');\n";
echo "    document.getElementById('jsDir').textContent = document.documentElement.getAttribute('dir');\n";
echo "  </script>\n";
echo "</div>\n";

// All Session Data
echo "<div class='box'>\n";
echo "  <h2>All Session Data</h2>\n";
echo "  <pre>" . print_r($_SESSION, true) . "</pre>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";
?>

