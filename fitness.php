<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Fitness';
$active = 'fitness';
$extraHead = '<script src="assets/js/page-fitness.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="fitness.title">Fitness</span></h1>
        <div class="ms-auto" style="max-width: 360px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="fitnessSearch" data-i18n-placeholder="fitness.searchPlaceholder" placeholder="Search fitness videos..." />
            </div>
        </div>
    </div>

    <div id="fitness-content" class="mb-5">
        <div class="category-videos" id="fitness-grid">
            <!-- Fitness cards will be dynamically loaded here -->
        </div>
        <div class="text-center py-5 loading-indicator">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden" data-i18n="common.loading">Loading...</span></div>
            <p class="mt-2" data-i18n="fitness.loadingContent">Loading fitness content...</p>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
