<?php
require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Home';
$active = 'home';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="home.title">Dashboard</span></h1>
        <!-- <div class="ms-auto" style="max-width: 360px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="gamesSearch" placeholder="Search games..." />
            </div>
        </div> -->
    </div>
    <div class="hero mb-4">
        <h1 class="display-4 fw-bold mb-3" data-i18n="home.welcomeTitle">Welcome to Streamify Pro</h1>
        <p class="text-muted mb-4" data-i18n="home.welcomeDescription">Unlock a world of learning for all ages. Access educational videos, interactive games, fitness activities, and enriching kids content in one platform.</p>
        <div>
            <a href="kids.php" class="btn btn-primary me-2"><i class="bi bi-play-fill me-2"></i><span data-i18n="home.startWatching">Start Watching</span></a>
            <a href="fitness.php" class="btn btn-outline-primary"><i class="bi bi-activity me-2"></i><span data-i18n="home.getFit">Get Fit</span></a>
        </div>
    </div>

    <div class="row g-3">
 
        <div class="col-md-4">
            <a class="text-decoration-none" href="kids.php">
                <div class="card shadow-sm p-4 h-100 position-relative">
                    <span id="educational-badge" class="badge bg-success position-absolute top-0 end-0 m-3" style="font-size: 0.7rem; font-weight: 600;" data-i18n="common.educational">Educational</span>
                    <div class="d-flex align-items-start">
                        <div class="icon-badge bg-success-subtle text-success me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <h5 class="mb-1" data-i18n="sidebar.kids">Kids</h5>
                            <p class="text-muted mb-2 small"><span id="kidsCount">—</span> <span data-i18n="home.kidsCard">educational videos for children</span></p>
                            <span class="link-primary small"><span data-i18n="home.watch">Watch</span> <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a class="text-decoration-none" href="fitness.php">
                <div class="card shadow-sm p-4 h-100 position-relative">
                    <span id="educational-badge" class="badge bg-success position-absolute top-0 end-0 m-3" style="font-size: 0.7rem; font-weight: 600;" data-i18n="common.educational">Educational</span>
                    <div class="d-flex align-items-start">
                        <div class="icon-badge bg-warning-subtle text-warning me-3">
                            <i class="bi bi-activity"></i>
                        </div>
                        <div>
                            <h5 class="mb-1" data-i18n="sidebar.fitness">Fitness</h5>
                            <p class="text-muted mb-2 small"><span id="fitnessCount">—</span> <span data-i18n="home.fitnessCard">workout videos</span></p>
                            <span class="link-primary small"><span data-i18n="home.startTraining">Start Training</span> <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a class="text-decoration-none" href="streaming.php">
                <div class="card shadow-sm p-4 h-100 position-relative">
                    <span  id="educational-badge" class="badge bg-primary position-absolute top-0 end-0 m-3" style="font-size: 0.7rem; font-weight: 600;" data-i18n="common.entertainment">Entertainment</span>
                    <div class="d-flex align-items-start">
                        <div class="icon-badge bg-primary-subtle text-primary me-3">
                            <i class="bi bi-film"></i>
                        </div>
                        <div>
                            <h5 class="mb-1" data-i18n="sidebar.streaming">Streaming</h5>
                            <p class="text-muted mb-2 small"><span id="streamingCount">—</span> <span data-i18n="home.streamingCard">videos and movies</span></p>
                            <span class="link-primary small"><span data-i18n="home.explore">Explore</span> <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a class="text-decoration-none" href="games.php">
                <div class="card shadow-sm p-4 h-100 position-relative">
                    <span  id="educational-badge" class="badge bg-primary position-absolute top-0 end-0 m-3" style="font-size: 0.7rem; font-weight: 600;" data-i18n="common.entertainment">Entertainment</span>
                    <div class="d-flex align-items-start">
                        <div class="icon-badge bg-danger-subtle text-danger me-3">
                            <i class="bi bi-joystick"></i>
                        </div>
                        <div>
                            <h5 class="mb-1" data-i18n="sidebar.games">Games</h5>
                            <p class="text-muted mb-2 small"><span id="gamesCount">—</span> <span data-i18n="home.gamesCard">HTML5 games ready to play</span></p>
                            <span class="link-primary small"><span data-i18n="home.playNow">Play Now</span> <i class="bi bi-arrow-right-short"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>


    </div>
</div>
</div>
<?php $extraScripts = '<script src="assets/js/page-home.js"></script>';
include __DIR__ . '/includes/footer.php'; ?>