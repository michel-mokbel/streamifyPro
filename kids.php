<?php require_once __DIR__ . '/includes/session.php';
require_auth();
 $pageTitle = 'Streamify Pro - Kids';
$active = 'kids';
$extraHead = '<script src="assets/js/page-kids.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px;">
    <h1 class="h2 mb-2"> <i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="kids.title">Kids</span></h1>
    <p class="text-muted mb-4" data-i18n="home.kidsCard">Educational and entertaining content for children</p>

    <div id="kids-channels" class="row g-4">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden" data-i18n="common.loading">Loading...</span></div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>