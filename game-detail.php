<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Game Details';
$active = 'games';
$extraHead = '<script src="assets/js/page-game-detail.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <h2 class="mb-4">
    <i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="gameDetail.pageTitle">Game Details</span></h2>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-4 rounded-4 overflow-hidden">
                <div class="ratio ratio-16x9">
                    <img id="gd-poster" src="" alt="" class="w-100 h-100" style="object-fit: cover;" />
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <h1 id="gd-title" class="h2 mb-0">Loading...</h1>
                    <span id="gd-premium" class="badge badge-premium ms-3 d-none">
                        <i class="bi bi-star-fill me-1"></i><span data-i18n="games.premium">Premium</span>
                    </span>
                </div>
                <p id="gd-desc" class="text-muted"></p>
            </div>

            <div class="mb-4">
                <button id="gd-play" class="btn btn-primary w-100 py-2 d-flex justify-content-center align-items-center">
                    <i class="bi bi-play-fill me-2"></i>
                    <span data-i18n="games.playGame">Play Game</span>
                    <i class="bi bi-box-arrow-up-right ms-2"></i>
                </button>
            </div>

            <div class="d-flex flex-wrap gap-4">
                <div>
                    <div class="text-muted small" data-i18n="gameDetail.plays">Plays</div>
                    <div class="d-flex align-items-center"><i class="bi bi-controller me-2 text-muted"></i><span id="gd-plays">0</span></div>
                </div>
                <div>
                    <div class="text-muted small" data-i18n="videoDetail.views">Views</div>
                    <div class="d-flex align-items-center"><i class="bi bi-eye me-2 text-muted"></i><span id="gd-views">0</span></div>
                </div>
                <div>
                    <div class="text-muted small" data-i18n="gameDetail.rating">Rating</div>
                    <div class="d-flex align-items-center"><i class="bi bi-star me-2 text-muted"></i><span id="gd-rating">Not rated</span></div>
                </div>
                <div>
                    <div class="text-muted small" data-i18n="common.actions">Actions</div>
                    <div class="d-flex gap-2">
                        <button class="action-icon favorite-btn" data-i18n-title="streaming.favorite" title="Add to Favorites">
                            <i class="bi bi-heart"></i>
                        </button>
                        <button class="action-icon watch-later-btn" data-i18n-title="streaming.watchLater" title="Watch Later">
                            <i class="bi bi-clock"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title" data-i18n="gameDetail.category">Game Collection</h5>
                    <div class="d-flex align-items-center mt-3">
                        <img id="gd-cat-img" src="" alt="" class="rounded" width="48" height="48" />
                        <div class="ms-3">
                            <h6 id="gd-cat-name" class="mb-1"></h6>
                            <p id="gd-cat-count" class="text-muted mb-0 small"></p>
                        </div>
                    </div>
                </div>
            </div>



            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title" id="gd-related-title" data-i18n="gameDetail.similarGames">More from this collection</h5>
                    <div id="gd-related" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>