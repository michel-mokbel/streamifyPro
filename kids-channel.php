<?php require_once __DIR__ . '/includes/session.php';
require_auth();
$pageTitle = 'Streamify Pro - Kids Channel';
$active = 'kids';
$extraHead = '<script src="assets/js/page-kids-channel.js" defer></script>';
include __DIR__ . '/includes/header.php'; ?>
<div class="container py-4 px-3 mx-auto" style="max-width: 1200px">
    <h2 class="mb-4"><i class="bi bi-list " id="sidebarToggle"></i><span data-i18n="kidsChannel.title">Kids Channel</span></h2>
    <div id="kc-header" class="mb-4"></div>

    <div id="kc-about" class="card shadow-sm mb-4 d-none">
        <div class="card-body d-flex">
            <img
                id="kc-profile"
                src=""
                alt=""
                class="rounded me-3"
                width="60"
                height="60" />
            <div>
                <h4 id="kc-name" class="mb-1"></h4>
                <p id="kc-desc" class="text-muted mb-0"></p>
            </div>
        </div>
    </div>

    <div id="kc-playlists"></div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>