<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Games';
$active = 'games';
$extraHead = '<script src="assets/js/page-games.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h2 mb-0">
        <i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="games.title">Games</span></h1>
        <!-- <div class="ms-auto" style="max-width: 360px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="gamesSearch" data-i18n-placeholder="games.searchPlaceholder" placeholder="Search games..." />
            </div>
        </div> -->
    </div>

    <div class="category-nav-container mb-4 position-relative">
        <button class="btn btn-light category-nav-arrow category-nav-prev" aria-label="Previous categories"><i class="bi bi-chevron-left"></i></button>
        <div class="category-nav-scroll">
            <div id="games-category-bubbles" class="category-bubbles"></div>
        </div>
        <button class="btn btn-light category-nav-arrow category-nav-next" aria-label="Next categories"><i class="bi bi-chevron-right"></i></button>
    </div>

    <div id="games-content" class="mb-5">
        <div class="text-center py-5 loading-indicator">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden" data-i18n="common.loading">Loading...</span></div>
            <p class="mt-2" data-i18n="games.loadingContent">Loading games...</p>
        </div>
    </div>
</div>
</div>
</div>

<template id="game-card-template">
    <div class="game-card-wrapper">
        <div class="card shadow-sm game-card">
            <div class="position-relative">
                <img src="" class="card-img-top" alt="" style="height: 180px; object-fit: cover;" />
                <span class="position-absolute top-0 end-0 m-2 badge badge-premium d-none premium-badge"><i class="bi bi-star-fill me-1"></i><span data-i18n="games.premium">Premium</span></span>
            </div>
            <div class="card-body p-3">
                <h5 class="card-title fs-6 game-title text-truncate"></h5>
                <p class="card-text small text-muted game-description text-truncate-2"></p>
            </div>
            <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center p-3">
                <div class="d-flex align-items-center"><i class="bi bi-controller text-primary me-1"></i><span class="game-playcount small"></span></div>
                <div class="d-flex">
                    <button class="action-icon me-2 favorite-btn"><i class="bi bi-heart"></i></button>
                    <button class="action-icon play-btn"><i class="bi bi-play-fill"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>
<?php include __DIR__ . '/includes/footer.php'; ?>