<?php require_once __DIR__ . '/session.php'; ?>
<?php
// Page variables
$pageTitle = isset($pageTitle) ? $pageTitle : 'Streamify Pro';
$active = isset($active) ? $active : '';
$user = function_exists('current_user') ? current_user() : null;
// Subscription date (created_at) lookup for logged-in user
$subscriptionDateFormatted = '';
if ($user) {
  require_once __DIR__ . '/config.php';
  if (isset($conn) && ($conn instanceof mysqli)) {
    if ($stmt = $conn->prepare('SELECT created_at FROM users WHERE user_id = ? LIMIT 1')) {
      $stmt->bind_param('i', $user['id']);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          $ts = strtotime((string)$row['created_at']);
          if ($ts) { $subscriptionDateFormatted = date('F j, Y', $ts); }
        }
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <!-- HLS.js for HLS video playback support -->
  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <script src="assets/js/i18n.js"></script>
  <script src="assets/js/core.js" defer></script>
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>

<body>
  <!-- <button class="sidebar-toggle" id="sidebarToggle">
    <i class="bi bi-list"></i>
  </button> -->
  <?php if ($user): ?>
  <!-- Account Modal -->
  <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-custom">
        <div class="modal-header">
          <h5 class="modal-title" id="accountModalLabel" data-i18n="account.title">Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-center mb-3">
            <i class="bi bi-person-circle fs-3 text-primary me-2"></i>
            <div>
              <div class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
              <div class="text-muted small"><span data-i18n="account.memberSince">Member since</span>: <?php echo $subscriptionDateFormatted !== '' ? htmlspecialchars($subscriptionDateFormatted) : '<span data-i18n="account.unknown">Unknown</span>'; ?></div>
            </div>
          </div>
          <div class="alert alert-light border mb-0">
            <span data-i18n="account.emailUs">Email us at</span>: <a href="mailto:info@streamifypro.com">info@streamifypro.com</a>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-i18n="common.close">Close</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <!-- Language Modal -->
  <div class="modal fade" id="languageModal" tabindex="-1" aria-labelledby="languageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-custom">
        <div class="modal-header">
          <h5 class="modal-title" id="languageModalLabel" data-i18n="sidebar.language">Language</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="language-options">
            <div class="row g-3">
              <div class="col-6">
                <button class="btn btn-outline-primary w-100 language-option" data-lang="en">
                  <div class="d-flex flex-column align-items-center">
                    <span class="fs-4 mb-2">🇺🇸</span>
                    <span class="fw-semibold">English</span>
                    <small class="text-muted">English</small>
                  </div>
                </button>
              </div>
              <div class="col-6">
                <button class="btn btn-outline-primary w-100 language-option" data-lang="ar">
                  <div class="d-flex flex-column align-items-center">
                    <span class="fs-4 mb-2">🇸🇦</span>
                    <span class="fw-semibold">العربية</span>
                    <small class="text-muted">Arabic</small>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="common.close">Close</button>
        </div>
      </div>
    </div>
  </div>
  
  <div class="d-flex">
    <div class="sidebar d-flex flex-column flex-shrink-0">
      <div class="d-flex flex-column align-items-center w-100">
        <img src="assets/img/logo1.png" alt="Streamify Pro" class="img-fluid mb-2" style="width: 140px; height: 140px;">
      </div>
 
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="home.php" class="nav-link <?php echo $active === 'home' ? 'active' : ''; ?>"><i class="bi bi-house-door"></i><span data-i18n="sidebar.home">Home</span></a></li>
        <li class="nav-item"><a href="kids.php" class="nav-link <?php echo $active === 'kids' ? 'active' : ''; ?>"><i class="bi bi-person-badge"></i><span data-i18n="sidebar.kids">Kids</span></a></li>
        <li class="nav-item"><a href="fitness.php" class="nav-link <?php echo $active === 'fitness' ? 'active' : ''; ?>"><i class="bi bi-activity"></i><span data-i18n="sidebar.fitness">Fitness</span></a></li>

        <li class="nav-item"><a href="streaming.php" class="nav-link <?php echo $active === 'streaming' ? 'active' : ''; ?>"><i class="bi bi-collection-play"></i><span data-i18n="sidebar.streaming">Streaming</span></a></li>
        <li class="nav-item"><a href="games.php" class="nav-link <?php echo $active === 'games' ? 'active' : ''; ?>"><i class="bi bi-controller"></i><span data-i18n="sidebar.games">Games</span></a></li>
        <li class="nav-item"><a href="favorites.php" class="nav-link <?php echo $active === 'favorites' ? 'active' : ''; ?>"><i class="bi bi-heart"></i><span data-i18n="sidebar.favorites">Favorites</span></a></li>
        <li class="nav-item"><a href="watchlater.php" class="nav-link <?php echo $active === 'watchlater' ? 'active' : ''; ?>"><i class="bi bi-clock-history"></i><span data-i18n="sidebar.watchLater">Watch Later</span></a></li>
        <?php if ($user): ?>
        <li class="nav-item"><a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#accountModal"><i class="bi bi-person-gear"></i><span data-i18n="sidebar.account">Account</span></a></li>
        <?php endif; ?>
        <li class="nav-item"><a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#languageModal"><i class="bi bi-translate"></i><span data-i18n="sidebar.language">Language</span></a></li>
      </ul>
      <?php if ($user): ?>
        <a href="logout.php" class="btn btn-light w-100"><i class="bi bi-box-arrow-right me-2"></i><span data-i18n="sidebar.logout">Logout</span></a>
      <?php else: ?>
        <a href="index.php" class="btn btn-signin w-100"><i class="bi bi-person-circle me-2"></i><span data-i18n="auth.signIn">Sign In</span></a>
      <?php endif; ?>
      <div class="copyright mt-3 small">© <?php echo date('Y'); ?> Streamify Pro. All rights reserved. Streamify Pro</div>
     
    </div>

    <div class="main-content">