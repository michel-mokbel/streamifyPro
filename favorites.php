<?php require_once __DIR__ . '/includes/session.php';
require_auth(); ?>
<?php $pageTitle = 'Streamify Pro - Favorites';
$active = 'favorites';
$extraHead = '<script src="assets/js/page-favorites.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <h1 class="h2 mb-3"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="favorites.title">Favorites</span></h1>
    <div id="fav-list" class="row g-3"></div>
</div>

<template id="game-card-template">
    <div class="game-card-wrapper">
        <div class="card shadow-sm game-card">
            <div class="position-relative">
                <img src="" class="card-img-top" alt="" style="height: 180px; object-fit: cover;" />
                <span class="position-absolute top-0 end-0 m-2 badge badge-premium d-none premium-badge"><i class="bi bi-star-fill me-1"></i>Premium</span>
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