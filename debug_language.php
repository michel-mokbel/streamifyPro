<?php
require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Language Debug';
$active = '';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1>Language System Debug</h1>
    
    <div class="card mb-3">
        <div class="card-header"><strong>PHP Session</strong></div>
        <div class="card-body">
            <p><strong>Session Language:</strong> <code><?= $_SESSION['ui_lang'] ?? 'NOT SET' ?></code></p>
            <p><strong>get_language():</strong> <code><?= get_language() ?></code></p>
            <p><strong>get_direction():</strong> <code><?= get_direction() ?></code></p>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header"><strong>HTML Attributes</strong></div>
        <div class="card-body">
            <p><strong>HTML lang attribute:</strong> <code id="htmlLang"></code></p>
            <p><strong>HTML dir attribute:</strong> <code id="htmlDir"></code></p>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header"><strong>JavaScript Detection</strong></div>
        <div class="card-body">
            <p><strong>document.documentElement.getAttribute('lang'):</strong> <code id="jsLang"></code></p>
            <p><strong>document.documentElement.getAttribute('dir'):</strong> <code id="jsDir"></code></p>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header"><strong>Test API Call</strong></div>
        <div class="card-body">
            <button class="btn btn-primary" id="testApiBtn">Test Fetch Games Data</button>
            <pre id="apiResult" class="mt-3" style="max-height: 300px; overflow: auto;"></pre>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><strong>Quick Language Switch</strong></div>
        <div class="card-body">
            <a href="set_language.php?lang=en" class="btn btn-info">Switch to English</a>
            <a href="set_language.php?lang=ar" class="btn btn-warning">Switch to Arabic</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Display HTML attributes
    document.getElementById('htmlLang').textContent = document.documentElement.getAttribute('lang') || 'NOT SET';
    document.getElementById('htmlDir').textContent = document.documentElement.getAttribute('dir') || 'NOT SET';
    
    // Display JavaScript detection
    document.getElementById('jsLang').textContent = document.documentElement.getAttribute('lang') || 'NOT SET';
    document.getElementById('jsDir').textContent = document.documentElement.getAttribute('dir') || 'NOT SET';
    
    // Test API button
    document.getElementById('testApiBtn').addEventListener('click', async function() {
        const resultEl = document.getElementById('apiResult');
        resultEl.textContent = 'Loading...';
        
        try {
            const lang = document.documentElement.getAttribute('lang') || 'en';
            const response = await fetch(`./api/api.php?route=games&lang=${lang}`);
            const data = await response.json();
            
            // Show first game with both EN and AR titles
            const firstGame = data.Content?.[0]?.HTML5?.[0]?.Content?.[0];
            if (firstGame) {
                resultEl.textContent = JSON.stringify({
                    lang: lang,
                    gameTitle_EN: firstGame.Title,
                    gameTitle_AR: firstGame.title_ar || 'NO ARABIC TRANSLATION',
                    gameDesc_EN: firstGame.Description,
                    gameDesc_AR: firstGame.description_ar || 'NO ARABIC TRANSLATION'
                }, null, 2);
            } else {
                resultEl.textContent = 'No games found in response';
            }
        } catch (e) {
            resultEl.textContent = 'Error: ' + e.message;
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

