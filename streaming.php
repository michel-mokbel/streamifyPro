<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Streaming';
$active = 'streaming';
$extraHead = '<script src="assets/js/page-streaming.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="streaming.title">Streaming</span></h1>
        <!-- <div class="ms-auto" style="max-width: 360px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="streamingSearch" data-i18n-placeholder="streaming.searchPlaceholder" placeholder="Search videos..." />
            </div>
        </div> -->
    </div>

    <div class="category-nav-container mb-4 position-relative">
        <button class="btn btn-light category-nav-arrow category-nav-prev" aria-label="Previous categories"><i class="bi bi-chevron-left"></i></button>
        <div class="category-nav-scroll">
            <div id="streaming-category-bubbles" class="category-bubbles"></div>
        </div>
        <button class="btn btn-light category-nav-arrow category-nav-next" aria-label="Next categories"><i class="bi bi-chevron-right"></i></button>
    </div>

    <div id="streaming-content" class="mb-5">
        <div class="text-center py-5 loading-indicator">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden" data-i18n="common.loading">Loading...</span></div>
            <p class="mt-2" data-i18n="streaming.loadingContent">Loading streaming content...</p>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>