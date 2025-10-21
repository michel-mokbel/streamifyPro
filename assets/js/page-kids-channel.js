/** Page: Kids Channel detail */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Listen for language changes and reload content
  window.addEventListener('languageChanged', init);

  async function init() {
    const params = new URLSearchParams(window.location.search);
    const channelId = params.get('id');
    if (!channelId) { window.location.replace('kids.php'); return; }
    const data = await fetchKids();
    const channel = (data.channels || []).find(c => String(c.id) === String(channelId));
    if (!channel) { document.querySelector('.container').innerHTML = `<div class="alert alert-danger mt-4">Channel not found.</div>`; return; }
    renderChannel(channel);
  }

  async function fetchKids() {
    // Get current language from i18n or localStorage
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    const res = await fetch(`./api/api.php?route=kids&lang=${currentLang}`);
    if (!res.ok) throw new Error('Failed to load kids');
    return res.json();
  }

  function renderChannel(channel) {
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    
    // Use translated fields if available (for Arabic), otherwise use original
    const channelName = (currentLang === 'ar' && channel.name_ar) ? channel.name_ar : channel.name;
    const channelDesc = (currentLang === 'ar' && channel.description_ar) ? channel.description_ar : (channel.description || '');
    
    const header = document.getElementById('kc-header');
    header.innerHTML = `
      <div class="rounded-4 overflow-hidden mb-3">
        <div class="ratio ratio-21x9">
          <img src="${channel.bannerImage || ''}" alt="${channelName}" class="w-100 h-100" style="object-fit: cover;">
        </div>
      </div>`;

    document.getElementById('kc-profile').src = channel.profileImage || '';
    document.getElementById('kc-name').textContent = channelName || '';
    document.getElementById('kc-desc').textContent = channelDesc || '';
    document.getElementById('kc-about').classList.remove('d-none');

    const lists = document.getElementById('kc-playlists');
    lists.innerHTML = '';
    let isFirst = true;
    (channel.playlists || []).forEach(playlist => {
      // Use translated playlist name if available
      const playlistName = (currentLang === 'ar' && playlist.name_ar) ? playlist.name_ar : playlist.name;
      
      const section = document.createElement('section');
      section.className = 'mb-3';
      const collapseId = `pl-${playlist.id}`;
      section.innerHTML = `
        <div class="card shadow-sm overflow-hidden">
          <div class="card-header bg-white d-flex align-items-center justify-content-between playlist-toggle" role="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${isFirst ? 'true' : 'false'}" aria-controls="${collapseId}">
            <div class="d-flex align-items-center">
              <img src="${playlist.profileImage || ''}" alt="${playlistName}" width="36" height="36" class="rounded me-2" />
              <h3 class="h5 mb-0">${playlistName}</h3>
            </div>
            <i class="bi bi-chevron-down chev"></i>
          </div>
          <div id="${collapseId}" class="collapse ${isFirst ? 'show' : ''}">
            <div class="card-body">
              <div class="row g-3"></div>
            </div>
          </div>
        </div>`;

      const grid = section.querySelector('.row');
      (playlist.content || []).forEach(video => {
        // Use translated video fields if available
        const videoTitle = (currentLang === 'ar' && video.title_ar) ? video.title_ar : video.title;
        const videoDesc = (currentLang === 'ar' && video.description_ar) ? video.description_ar : (video.description || '');
        
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-xl-3';
        col.innerHTML = `
          <div class="card h-100 shadow-sm video-card" style="cursor:pointer;">
            <div class="thumb-wrapper" style="height: 160px;">
              <img src="${video.imageCropped || video.imageFile || ''}" class="card-img-top" alt="${videoTitle}" style="height: 160px; object-fit: cover;">
              <div class="thumb-play"><i class="bi bi-play-fill fs-4"></i></div>
            </div>
            <div class="card-body p-3">
              <h5 class="card-title fs-6 text-truncate">${videoTitle}</h5>
              <p class="card-text small text-muted text-truncate-2">${videoDesc}</p>
            </div>
            <div class="card-footer bg-white border-top-0 d-flex justify-content-end align-items-center p-3">
              <div class="d-flex">
                <button class="action-icon me-2 favorite-btn" title="Favorite"><i class="bi bi-heart"></i></button>
                <button class="action-icon watch-later-btn" title="Watch Later"><i class="bi bi-clock"></i></button>
              </div>
            </div>
          </div>`;
        col.querySelector('.video-card').addEventListener('click', (e) => {
          if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
          window.location.href = `kids-video.php?channel=${encodeURIComponent(channel.id)}&playlist=${encodeURIComponent(playlist.id)}&video=${encodeURIComponent(video.id)}`;
        });
        // Favorite button wiring
        const favBtn = col.querySelector('.favorite-btn');
        const favIcon = favBtn.querySelector('i');
        function setFavUI(active) {
          favBtn.classList.toggle('text-danger', active);
          favIcon.classList.toggle('bi-heart-fill', active);
          favIcon.classList.toggle('bi-heart', !active);
        }
        try {
          if (window.readCookieList && window.COOKIE_KEYS) {
            const list = readCookieList(COOKIE_KEYS.favorites);
            const active = Array.isArray(list) && list.some(x => String(x.id) === String(video.id) && x.type === 'kids');
            setFavUI(active);
          }
        } catch (_) {}
        favBtn.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
          if (typeof toggleFavorite === 'function') {
            toggleFavorite('kids', { id: video.id, title: video.title, imageFile: video.imageFile || video.imageCropped, sourceFile: video.sourceFile || '' });
            const nowActive = !favIcon.classList.contains('bi-heart-fill');
            setFavUI(nowActive);
          }
        });
        // Watch later button wiring
        const wlBtn = col.querySelector('.watch-later-btn');
        const wlIcon = wlBtn.querySelector('i');
        function setWlUI(active) {
          wlBtn.classList.toggle('active', active);
          wlIcon.classList.toggle('bi-clock-fill', active);
          wlIcon.classList.toggle('bi-clock', !active);
        }
        try {
          if (window.readCookieList && window.COOKIE_KEYS) {
            const list = readCookieList(COOKIE_KEYS.watchLater);
            const active = Array.isArray(list) && list.some(x => String(x.id) === String(video.id) && x.type === 'kids');
            setWlUI(active);
          }
        } catch (_) {}
        wlBtn.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
          if (typeof toggleWatchLater === 'function') {
            toggleWatchLater('kids', { id: video.id, title: video.title, imageFile: video.imageFile || video.imageCropped, sourceFile: video.sourceFile || '' });
            const nowActive = !wlIcon.classList.contains('bi-clock-fill');
            setWlUI(nowActive);
          }
        });
        grid.appendChild(col);
      });

      lists.appendChild(section);
      isFirst = false;
    });
  }
})();


