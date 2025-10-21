<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Kids Video';
$active = 'kids';
$extraHead = '<script src="assets/js/page-kids-video.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px">
<h2 class="mb-4"> <i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="kidsVideo.title">Kids Video</span></h2 >

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3 rounded-4 overflow-hidden position-relative">
                <div class="ratio ratio-16x9 bg-dark">
                    <video id="kv-video" class="w-100 h-100" controls preload="metadata" poster="" style="object-fit: contain;">
                        <source id="kv-video-source" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
            <div
                class="d-flex justify-content-between align-items-center mb-3">
                <button id="kv-prev" class="btn btn-outline-secondary">
                    <i class="bi bi-skip-backward-fill me-1"></i> <span data-i18n="common.previous">Previous</span>
                </button>
                <button id="kv-next" class="btn btn-outline-secondary">
                    <span data-i18n="common.next">Next</span> <i class="bi bi-skip-forward-fill ms-1"></i>
                </button>
            </div>

            <div class="mb-4">
                <h1 id="kv-title" class="h2 mb-2">Loading...</h1>
                <p id="kv-desc" class="text-muted"></p>
            </div>


            <div class="mb-3 d-flex flex-wrap gap-4">
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
                    <h5 class="card-title" data-i18n="kidsVideo.playlist">Playlist</h5>
                    <div class="d-flex align-items-center mt-3">
                        <img
                            id="kv-pl-img"
                            src=""
                            alt=""
                            class="rounded"
                            width="48"
                            height="48" />
                        <div class="ms-3">
                            <h6 id="kv-pl-name" class="mb-1"></h6>
                            <p id="kv-pl-count" class="text-muted mb-0 small"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title" data-i18n="kidsVideo.moreFromPlaylist">More from this playlist</h5>
                    <div id="kv-related" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>