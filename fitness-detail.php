<?php 
require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Fitness Video';
$active = 'fitness';
$extraHead = '<script src="assets/js/page-fitness-detail.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <h2 class="mb-4"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="fitnessDetail.title">Fitness Video</span></h2>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mb-3 rounded-4 overflow-hidden position-relative">
                <div class="ratio ratio-16x9 bg-dark">
                    <video id="fitness-video" class="w-100 h-100" controls preload="metadata" poster="" style="object-fit: contain;">
                        <source id="fitness-video-source" src="" type="video/quicktime">
                        <source id="fitness-video-source-mp4" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <button id="fitness-prev" class="btn btn-outline-secondary">
                    <i class="bi bi-skip-backward-fill me-1"></i> <span data-i18n="common.previous">Previous</span>
                </button>
                <button id="fitness-next" class="btn btn-outline-secondary">
                    <span data-i18n="common.next">Next</span> <i class="bi bi-skip-forward-fill ms-1"></i>
                </button>
            </div>

            <div class="mb-4">
                <h1 id="fitness-title" class="h2 mb-2">Loading...</h1>
                
                <!-- Description Section -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title d-flex align-items-center">
                            <i class="bi bi-info-circle me-2 text-primary"></i>
                            <span data-i18n="fitnessDetail.description">Description</span>
                        </h6>
                        <p id="fitness-description" class="card-text mb-0">Loading description...</p>
                    </div>
                </div>
                
                <!-- Tips Section -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title d-flex align-items-center">
                            <i class="bi bi-lightbulb me-2 text-warning"></i>
                            <span data-i18n="fitnessDetail.tips">Tips</span>
                        </h6>
                        <p id="fitness-tips" class="card-text mb-0">Loading tips...</p>
                    </div>
                </div>
                
                <!-- Sets & Reps Section -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title d-flex align-items-center">
                            <i class="bi bi-repeat me-2 text-success"></i>
                            <span data-i18n="fitnessDetail.setsReps">Sets & Reps</span>
                        </h6>
                        <p id="fitness-sets-reps" class="card-text mb-0">Loading sets and reps...</p>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-4">
                <div>
                    <div class="text-muted small" data-i18n="videoDetail.category">Category</div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-tag me-2 text-success"></i>
                        <span id="fitness-category">Loading...</span>
                    </div>
                </div>

                <div>
                    <div class="text-muted small">Actions</div>
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

            <div class="mt-4">
                <a href="fitness.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i><span data-i18n="common.back">Back to Fitness</span>
                </a>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title" data-i18n="fitnessDetail.moreFitnessVideos">More Fitness Videos</h5>
                    <div id="fitness-related" class="mt-3">
                        <!-- Related videos will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
