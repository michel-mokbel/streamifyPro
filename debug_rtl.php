<?php
require_once __DIR__ . '/includes/session.php';

$currentLang = get_language();
$currentDir = get_direction();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentDir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTL & Translation Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .debug-box { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .info-item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-item:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #495057; }
        .info-value { color: #212529; font-family: monospace; }
        .visual-test { padding: 20px; border: 2px dashed #007bff; margin: 10px 0; }
        .test-card { min-height: 100px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">🔍 RTL & Translation Diagnostics</h1>

        <!-- Session Info -->
        <div class="debug-box">
            <h2 class="h4 mb-3">📦 PHP Session</h2>
            <div class="info-item">
                <span class="info-label">Session Status:</span>
                <span class="info-value <?= session_status() === PHP_SESSION_ACTIVE ? 'status-ok' : 'status-error' ?>">
                    <?= session_status() === PHP_SESSION_ACTIVE ? '✅ ACTIVE' : '❌ INACTIVE' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">$_SESSION['ui_lang']:</span>
                <span class="info-value"><?= isset($_SESSION['ui_lang']) ? $_SESSION['ui_lang'] : '❌ NOT SET' ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">get_language():</span>
                <span class="info-value"><?= $currentLang ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">get_direction():</span>
                <span class="info-value"><?= $currentDir ?></span>
            </div>
        </div>

        <!-- HTML Attributes -->
        <div class="debug-box">
            <h2 class="h4 mb-3">🏷️ HTML Attributes</h2>
            <div class="info-item">
                <span class="info-label">PHP Output (lang):</span>
                <span class="info-value"><?= htmlspecialchars($currentLang) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">PHP Output (dir):</span>
                <span class="info-value"><?= htmlspecialchars($currentDir) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">JavaScript Reading (lang):</span>
                <span class="info-value" id="js-lang">Loading...</span>
            </div>
            <div class="info-item">
                <span class="info-label">JavaScript Reading (dir):</span>
                <span class="info-value" id="js-dir">Loading...</span>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="debug-box">
            <h2 class="h4 mb-3">🌐 Language Switcher</h2>
            <p>Click to switch language and see if it persists:</p>
            <a href="set_language.php?lang=en" class="btn btn-primary me-2">
                🇺🇸 Switch to English
            </a>
            <a href="set_language.php?lang=ar" class="btn btn-success">
                🇸🇦 Switch to Arabic
            </a>
        </div>

        <!-- Translation Test -->
        <div class="debug-box">
            <h2 class="h4 mb-3">🔤 Translation Test</h2>
            <p class="mb-3">These elements should translate automatically:</p>
            <div class="alert alert-info">
                <strong data-i18n="home.title">Dashboard</strong> - 
                <span data-i18n="home.welcomeTitle">Welcome to Streamify Pro</span>
            </div>
            <div id="translation-status" class="mt-2"></div>
        </div>

        <!-- RTL Layout Test -->
        <div class="debug-box">
            <h2 class="h4 mb-3">↔️ RTL Layout Test</h2>
            <div class="visual-test">
                <p><strong>English Text:</strong> This text should align left in LTR mode and right in RTL mode.</p>
                <p><strong>Arabic Text:</strong> مرحبا بك في تطبيق Streamify Pro</p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary">Button 1</button>
                    <button class="btn btn-secondary">Button 2</button>
                    <button class="btn btn-info">Button 3</button>
                </div>
            </div>
            <div id="rtl-status" class="mt-2"></div>
        </div>

        <!-- Card Rendering Test -->
        <div class="debug-box">
            <h2 class="h4 mb-3">🎴 Card Rendering Test</h2>
            <p class="mb-3">Simulated card with Arabic and English fields:</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="card test-card">
                        <div class="card-body">
                            <h5 class="card-title" id="test-card-title">Loading...</h5>
                            <p class="card-text" id="test-card-desc">Loading...</p>
                            <span class="badge bg-primary" id="test-card-category">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="card-status" class="mt-3"></div>
        </div>

        <!-- Console Log Section -->
        <div class="debug-box">
            <h2 class="h4 mb-3">📝 Console Logs</h2>
            <p>Check your browser's console (F12) for detailed logging. Look for messages starting with:</p>
            <ul>
                <li>✅ i18n: Translations loaded</li>
                <li>🔤 i18n: Found X elements to translate</li>
                <li>✅ i18n: Translated X/Y elements</li>
            </ul>
            <div id="console-output" class="alert alert-secondary" style="font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                Logs will appear here...
            </div>
        </div>
    </div>

    <script src="./assets/js/i18n.js"></script>
    <script>
        // Diagnostic Script
        (function() {
            const consoleOutput = document.getElementById('console-output');
            const originalConsole = {
                log: console.log,
                error: console.error,
                warn: console.warn
            };

            function addToOutput(type, ...args) {
                const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ');
                const timestamp = new Date().toLocaleTimeString();
                const div = document.createElement('div');
                div.textContent = `[${timestamp}] ${type.toUpperCase()}: ${msg}`;
                div.style.color = type === 'error' ? '#dc3545' : type === 'warn' ? '#ffc107' : '#000';
                consoleOutput.appendChild(div);
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }

            console.log = function(...args) {
                originalConsole.log.apply(console, args);
                addToOutput('log', ...args);
            };

            console.error = function(...args) {
                originalConsole.error.apply(console, args);
                addToOutput('error', ...args);
            };

            console.warn = function(...args) {
                originalConsole.warn.apply(console, args);
                addToOutput('warn', ...args);
            };

            // Run diagnostics after page load
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🔍 Starting diagnostics...');

                // Check HTML attributes
                const htmlLang = document.documentElement.getAttribute('lang');
                const htmlDir = document.documentElement.getAttribute('dir');
                
                document.getElementById('js-lang').textContent = htmlLang || '❌ NOT SET';
                document.getElementById('js-dir').textContent = htmlDir || '❌ NOT SET';

                console.log(`HTML lang attribute: ${htmlLang}`);
                console.log(`HTML dir attribute: ${htmlDir}`);

                // Check if i18n is loaded
                setTimeout(() => {
                    if (typeof window.i18n !== 'undefined') {
                        console.log('✅ i18n module loaded');
                        
                        // Test translation function
                        const testTranslation = window.i18n.t('home.title');
                        console.log(`Test translation (home.title): "${testTranslation}"`);
                        
                        document.getElementById('translation-status').innerHTML = 
                            `<div class="alert alert-success">✅ i18n module is working. Test translation: "${testTranslation}"</div>`;
                    } else {
                        console.error('❌ i18n module not loaded!');
                        document.getElementById('translation-status').innerHTML = 
                            `<div class="alert alert-danger">❌ i18n module failed to load!</div>`;
                    }

                    // Test RTL
                    const computedDir = window.getComputedStyle(document.documentElement).direction;
                    console.log(`Computed CSS direction: ${computedDir}`);
                    
                    const isRTL = computedDir === 'rtl' || htmlDir === 'rtl';
                    document.getElementById('rtl-status').innerHTML = isRTL
                        ? `<div class="alert alert-success">✅ RTL mode is ACTIVE</div>`
                        : `<div class="alert alert-info">ℹ️ LTR mode is active (this is normal for English)</div>`;

                    // Test card rendering
                    const mockItem = {
                        Title: "Sample Game Title",
                        title_ar: "عنوان اللعبة النموذجية",
                        Description: "This is a sample description in English.",
                        description_ar: "هذا وصف نموذجي باللغة العربية.",
                        category: "Action",
                        category_ar: "أكشن"
                    };

                    const currentLang = htmlLang || 'en';
                    const title = currentLang === 'ar' && mockItem.title_ar ? mockItem.title_ar : mockItem.Title;
                    const desc = currentLang === 'ar' && mockItem.description_ar ? mockItem.description_ar : mockItem.Description;
                    const category = currentLang === 'ar' && mockItem.category_ar ? mockItem.category_ar : mockItem.category;

                    document.getElementById('test-card-title').textContent = title;
                    document.getElementById('test-card-desc').textContent = desc;
                    document.getElementById('test-card-category').textContent = category;

                    console.log(`Card rendered with language: ${currentLang}`);
                    console.log(`Card title: "${title}"`);
                    console.log(`Card description: "${desc}"`);
                    console.log(`Card category: "${category}"`);

                    document.getElementById('card-status').innerHTML = currentLang === 'ar'
                        ? `<div class="alert alert-success">✅ Arabic content should be displayed above</div>`
                        : `<div class="alert alert-info">ℹ️ English content should be displayed above</div>`;

                    console.log('✅ Diagnostics complete!');
                }, 1000);
            });
        })();
    </script>
</body>
</html>

