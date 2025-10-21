(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', initWatchLaterPage);
  
  // Listen for language changes and reload content
  window.addEventListener('languageChanged', initWatchLaterPage);

  function initWatchLaterPage() {
    const container = document.getElementById('wl-list');
    if (!container) {
      console.log('WatchLater container not found');
      return;
    }

    const list = (window.readCookieList && window.COOKIE_KEYS)
      ? readCookieList(COOKIE_KEYS.watchLater)
      : [];

    console.log('WatchLater list:', list);

    if (!Array.isArray(list) || list.length === 0) {
      let noItemsText = 'No items saved for later.';
      if (window.i18n && window.i18n.t) {
        const translated = window.i18n.t('watchLater.noItems');
        // Only use translation if it's not the key itself
        if (translated !== 'watchLater.noItems') {
          noItemsText = translated;
        }
      }
      console.log('Setting empty state:', noItemsText);
      container.innerHTML = `<div class="alert alert-info">${noItemsText}</div>`;
      return;
    }

    // Separate by type
    const gameItems = list.filter(x => x.type === 'games');
    const streamingItems = list.filter(x => x.type === 'streaming');
    const kidsItems = list.filter(x => x.type === 'kids');
    const fitnessItems = list.filter(x => x.type === 'fitness');

    const frag = document.createDocumentFragment();

    gameItems.forEach(item => {
      const card = createWatchLaterGameCardFromTemplate(item);
      if (card) frag.appendChild(card);
    });

    streamingItems.forEach(item => {
      const card = createWatchLaterStreamingCard(item);
      if (card) frag.appendChild(card);
    });

    kidsItems.forEach(item => {
      const card = createWatchLaterKidsCard(item);
      if (card) frag.appendChild(card);
    });

    fitnessItems.forEach(item => {
      const card = createWatchLaterFitnessCard(item);
      if (card) frag.appendChild(card);
    });

    container.innerHTML = '';
    container.appendChild(frag);
  }

  function createWatchLaterGameCardFromTemplate(item) {
    const tpl = document.getElementById('game-card-template');
    if (!tpl) return null;
    const frag = tpl.content.cloneNode(true);

    const root = frag.querySelector('.game-card');
    const title = frag.querySelector('.game-title');
    const desc = frag.querySelector('.game-description');
    const img = frag.querySelector('.card-img-top');
    const playcount = frag.querySelector('.game-playcount');
    const premium = frag.querySelector('.premium-badge');
    const favBtn = frag.querySelector('.favorite-btn');
    const playBtn = frag.querySelector('.play-btn');
    const footerActions = frag.querySelector('.card-footer .d-flex');

    const idStr = String(item.id);

    title.textContent = item.title || 'Untitled Game';
    desc.textContent = '';
    playcount.textContent = 'New';
    premium.classList.add('d-none');
    window.applyLazyLoading(img, item.image || window.PLACEHOLDER_IMAGE, item.title || 'Game');

    // Favorite toggle state
    const favIcon = favBtn.querySelector('i');
    function setFavUI(active) {
      favIcon.classList.toggle('bi-heart-fill', active);
      favIcon.classList.toggle('bi-heart', !active);
      favBtn.classList.toggle('text-danger', active);
    }
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const favs = readCookieList(COOKIE_KEYS.favorites);
        const active = Array.isArray(favs) && favs.some(x => String(x.id) === idStr && x.type === 'games');
        setFavUI(active);
      }
    } catch (_) {}

    // Toggle favorite from watch later card
    favBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleFavorite === 'function') {
        toggleFavorite('games', { ID: item.id, Title: item.title, Thumbnail: item.image, Content: item.url });
        const nowActive = !favIcon.classList.contains('bi-heart-fill');
        setFavUI(nowActive);
      }
    });

    // Add a Watch Later button (clock) to allow removal from watch later list
    if (footerActions) {
      const wlBtn = document.createElement('button');
      wlBtn.className = 'action-icon watch-later-btn ms-2';
      wlBtn.title = 'Watch Later';
      wlBtn.innerHTML = '<i class="bi bi-clock"></i>';
      footerActions.insertBefore(wlBtn, playBtn.closest('.action-icon') || null);

      const wlIcon = wlBtn.querySelector('i');
      function setWlUI(active) {
        wlBtn.classList.toggle('active', active);
        wlIcon.classList.toggle('bi-clock-fill', active);
        wlIcon.classList.toggle('bi-clock', !active);
      }
      try {
        const list = readCookieList(COOKIE_KEYS.watchLater);
        setWlUI(Array.isArray(list) && list.some(x => String(x.id) === idStr && x.type === 'games'));
      } catch (_) {}
      wlBtn.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
        if (typeof toggleWatchLater === 'function') {
          toggleWatchLater('games', { ID: item.id, Title: item.title, Thumbnail: item.image, Content: item.url });
          const nowActive = !wlIcon.classList.contains('bi-clock-fill');
          setWlUI(nowActive);
          if (!nowActive) {
            // Removed from watch later -> remove card immediately
            const wrapper = root.closest('.game-card-wrapper');
            if (wrapper && wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);
            ensureWatchLaterNotEmpty();
          }
        }
      });
    }

    // Play button
    playBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (item.url) {
        window.open(item.url, '_blank');
      }
    });

    // Card navigation to detail page
    root.addEventListener('click', (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      const id = encodeURIComponent(String(item.id));
      window.location.href = `game-detail.php?id=${id}`;
    });

    return frag;
  }

  function createWatchLaterStreamingCard(item) {
    const wrap = document.createElement('div');
    wrap.className = 'streaming-card-wrapper';
    const viewsText = window.i18n?.t('streaming.views') || 'views';
    const favoriteText = window.i18n?.t('streaming.favorite') || 'Favorite';
    const watchLaterText = window.i18n?.t('streaming.watchLater') || 'Watch Later';
    
    // Get current language and use Arabic translations if available
    const currentLang = localStorage.getItem('streamify_language') || 'en';
    const videoTitle = currentLang === 'ar' && item.title_ar ? item.title_ar : item.title;
    
    wrap.innerHTML = `
      <div class="card shadow-sm h-100">
        <div class="position-relative">
          <img class="card-img-top" alt="" style="height: 180px; object-fit: cover;" />
        </div>
        <div class="card-body p-3">
          <h5 class="card-title fs-6 text-truncate">${videoTitle || 'Untitled'}</h5>
          <p class="card-text small text-muted mb-0">${formatNumber(getSyntheticVideoMetrics({ Id: item.id, Title: item.title }).views)} ${viewsText}</p>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-end align-items-center p-3">
          <div class="d-flex">
            <button class="action-icon me-2 favorite-btn" title="${favoriteText}"><i class="bi bi-heart"></i></button>
            <button class="action-icon watch-later-btn" title="${watchLaterText}"><i class="bi bi-clock"></i></button>
          </div>
        </div>
      </div>`;

    const img = wrap.querySelector('img');
    window.applyLazyLoading(img, item.image || window.PLACEHOLDER_IMAGE, item.title || 'Video');

    // Navigate to detail
    wrap.querySelector('.card').addEventListener('click', (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      const id = encodeURIComponent(String(item.id));
      window.location.href = `video-detail.php?id=${id}`;
    });

    // Favorite
    const favBtn = wrap.querySelector('.favorite-btn');
    const favIcon = favBtn.querySelector('i');
    function setFavUI(active) { favBtn.classList.toggle('text-danger', active); favIcon.classList.toggle('bi-heart-fill', active); favIcon.classList.toggle('bi-heart', !active); }
    try { const list = readCookieList(COOKIE_KEYS.favorites); setFavUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'streaming')); } catch (_) {}
    favBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); toggleFavorite('streaming', { Id: item.id, Title: item.title, Thumbnail: item.image, Content: item.url }); const nowActive = !favIcon.classList.contains('bi-heart-fill'); setFavUI(nowActive); });

    // Watch later
    const wlBtn = wrap.querySelector('.watch-later-btn');
    const wlIcon = wlBtn.querySelector('i');
    function setWlUI(active) { wlBtn.classList.toggle('active', active); wlIcon.classList.toggle('bi-clock-fill', active); wlIcon.classList.toggle('bi-clock', !active); }
    try { const list = readCookieList(COOKIE_KEYS.watchLater); setWlUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'streaming')); } catch (_) {}
    wlBtn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      toggleWatchLater('streaming', { Id: item.id, Title: item.title, Thumbnail: item.image, Content: item.url });
      const nowActive = !wlIcon.classList.contains('bi-clock-fill');
      setWlUI(nowActive);
      if (!nowActive) {
        // Removed from watch later -> remove card immediately
        if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
        ensureWatchLaterNotEmpty();
      }
    });

    return wrap;
  }

  function createWatchLaterKidsCard(item) {
    const wrap = document.createElement('div');
    wrap.className = 'streaming-card-wrapper';
    const imgSrc = item.image || window.PLACEHOLDER_IMAGE;
    wrap.innerHTML = `
      <div class="card h-100 shadow-sm video-card" style="cursor:pointer;">
        <div class="thumb-wrapper" style="height: 180px;">
          <img src="${imgSrc}" class="card-img-top" alt="${item.title || ''}" style="height: 180px; object-fit: cover;">
          <div class="thumb-play"><i class="bi bi-play-fill fs-4"></i></div>
        </div>
        <div class="card-body p-3">
          <h5 class="card-title fs-6 text-truncate">${item.title || ''}</h5>
          <p class="card-text small text-muted text-truncate-2"></p>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-end align-items-center p-3">
          <div class="d-flex">
            <button class="action-icon me-2 favorite-btn" title="Favorite"><i class="bi bi-heart"></i></button>
            <button class="action-icon watch-later-btn" title="Watch Later"><i class="bi bi-clock"></i></button>
          </div>
        </div>
      </div>`;

    wrap.querySelector('.video-card').addEventListener('click', async (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      const path = await findKidsPathByVideoId(String(item.id));
      if (path) {
        window.location.href = `kids-video.php?channel=${encodeURIComponent(path.channel)}&playlist=${encodeURIComponent(path.playlist)}&video=${encodeURIComponent(item.id)}`;
      } else if (item.url) {
        window.open(item.url, '_blank');
      }
    });

    // Favorite
    const favBtn = wrap.querySelector('.favorite-btn');
    const favIcon = favBtn.querySelector('i');
    function setFavUI(active) { favBtn.classList.toggle('text-danger', active); favIcon.classList.toggle('bi-heart-fill', active); favIcon.classList.toggle('bi-heart', !active); }
    try { const list = readCookieList(COOKIE_KEYS.favorites); setFavUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'kids')); } catch (_) {}
    favBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); toggleFavorite('kids', { id: item.id, title: item.title, imageFile: item.image, sourceFile: item.url }); const nowActive = !favIcon.classList.contains('bi-heart-fill'); setFavUI(nowActive); });

    // Watch later
    const wlBtn = wrap.querySelector('.watch-later-btn');
    const wlIcon = wlBtn.querySelector('i');
    function setWlUI(active) { wlBtn.classList.toggle('active', active); wlIcon.classList.toggle('bi-clock-fill', active); wlIcon.classList.toggle('bi-clock', !active); }
    try { const list = readCookieList(COOKIE_KEYS.watchLater); setWlUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'kids')); } catch (_) {}
    wlBtn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      toggleWatchLater('kids', { id: item.id, title: item.title, imageFile: item.image, sourceFile: item.url });
      const nowActive = !wlIcon.classList.contains('bi-clock-fill');
      setWlUI(nowActive);
      if (!nowActive) {
        if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
        ensureWatchLaterNotEmpty();
      }
    });

    return wrap;
  }

  async function findKidsPathByVideoId(videoId) {
    try {
      if (!findKidsPathByVideoId._cache) {
        const res = await fetch('./api/api.php?route=kids');
        if (!res.ok) throw new Error('kids fetch failed');
        findKidsPathByVideoId._cache = await res.json();
      }
      const data = findKidsPathByVideoId._cache || {};
      let found = null;
      (data.channels || []).forEach(ch => {
        (ch.playlists || []).forEach(pl => {
          (pl.content || []).forEach(v => {
            if (found) return;
            if (String(v.id) === String(videoId)) {
              found = { channel: ch.id, playlist: pl.id };
            }
          });
        });
      });
      return found;
    } catch (e) {
      return null;
    }
  }

  // Synthetic metrics for streaming
  function getSyntheticVideoMetrics(video) {
    const key = String(video.Id || video.ID || video.Title || Math.random());
    const views = clampInt(randFromKey(key + ':views'), 5000, 3000000);
    const ratingRaw = 3.6 + (randFromKey(key + ':rating') / 4294967295) * (4.9 - 3.6);
    const rating = Math.round(ratingRaw * 10) / 10;
    return { views, rating };
  }

  function createWatchLaterFitnessCard(item) {
    const wrap = document.createElement('div');
    wrap.className = 'streaming-card-wrapper';
    const fitnessText = window.i18n?.t('sidebar.fitness') || 'Fitness';
    const fitnessVideoText = window.i18n?.t('fitnessDetail.title') || 'Fitness Video';
    const workoutText = window.i18n?.t('fitness.workoutVideo') || 'Workout Video';
    const favoriteText = window.i18n?.t('streaming.favorite') || 'Favorite';
    const watchLaterText = window.i18n?.t('streaming.watchLater') || 'Watch Later';
    
    // Get current language and use Arabic title if available
    const currentLang = localStorage.getItem('streamify_language') || 'en';
    const title = currentLang === 'ar' && item.title_ar ? item.title_ar : (item.title || fitnessVideoText);
    
    // Category logic: Use Arabic category if in Arabic, otherwise use English category
    // Note: category_en and category_ar are only available in fitness-ar.json
    let category = 'Fitness'; // Default fallback
    if (currentLang === 'ar' && item.category_ar) {
      category = item.category_ar;
    } else if (item.category_en) {
      category = item.category_en;
    }
    
    wrap.innerHTML = `
      <div class="card shadow-sm h-100">
        <div class="position-relative">
          <img class="card-img-top fitness-thumbnail" alt="" style="height: 180px; object-fit: cover;" 
               data-video-url="${item.url || ''}" 
               data-video-id="${item.id || ''}" />
          <span class="position-absolute bottom-0 end-0 m-2 badge bg-primary">
            <i class="bi bi-activity me-1"></i>${fitnessText}
          </span>
        </div>
        <div class="card-body p-3">
          <h5 class="card-title fs-6 text-truncate">${title}</h5>
          <p class="card-text small text-muted mb-1">${category}</p>
          <p class="card-text small text-muted mb-0">${workoutText}</p>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-end align-items-center p-3">
          <div class="d-flex">
            <button class="action-icon me-2 favorite-btn" title="${favoriteText}"><i class="bi bi-heart"></i></button>
            <button class="action-icon watch-later-btn" title="${watchLaterText}"><i class="bi bi-clock"></i></button>
          </div>
        </div>
      </div>`;

    const img = wrap.querySelector('img');
    const placeholderUrl = item.image || window.PLACEHOLDER_IMAGE || 'https://dummyimage.com/640x360/3182ff/ffffff.png&text=Fitness';
    img.src = placeholderUrl;
    
    // Try to generate thumbnail from video URL if available
    if (item.url && typeof generateVideoThumbnail === 'function') {
      generateVideoThumbnail(item.url, (thumbnailUrl) => {
        if (thumbnailUrl) {
          img.src = thumbnailUrl;
        }
      }, item.id);
    }

    // Navigate to detail
    wrap.querySelector('.card').addEventListener('click', (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      const id = encodeURIComponent(String(item.id));
      window.location.href = `fitness-detail.php?id=${id}`;
    });

    // Favorite
    const favBtn = wrap.querySelector('.favorite-btn');
    const favIcon = favBtn.querySelector('i');
    function setFavUI(active) { favBtn.classList.toggle('text-danger', active); favIcon.classList.toggle('bi-heart-fill', active); favIcon.classList.toggle('bi-heart', !active); }
    try { const list = readCookieList(COOKIE_KEYS.favorites); setFavUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'fitness')); } catch (_) {}
    favBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); toggleFavorite('fitness', { Id: item.id, Title: item.title, Thumbnail: item.image, Content: item.url }); const nowActive = !favIcon.classList.contains('bi-heart-fill'); setFavUI(nowActive); });

    // Watch later
    const wlBtn = wrap.querySelector('.watch-later-btn');
    const wlIcon = wlBtn.querySelector('i');
    function setWlUI(active) { wlBtn.classList.toggle('active', active); wlIcon.classList.toggle('bi-clock-fill', active); wlIcon.classList.toggle('bi-clock', !active); }
    try { const list = readCookieList(COOKIE_KEYS.watchLater); setWlUI(Array.isArray(list) && list.some(x => String(x.id) === String(item.id) && x.type === 'fitness')); } catch (_) {}
    wlBtn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      toggleWatchLater('fitness', { Id: item.id, Title: item.title, Thumbnail: item.image, Content: item.url });
      const nowActive = !wlIcon.classList.contains('bi-clock-fill');
      setWlUI(nowActive);
      if (!nowActive) {
        if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
        ensureWatchLaterNotEmpty();
      }
    });

    return wrap;
  }

  function randFromKey(key) { let h = 2166136261 >>> 0; for (let i = 0; i < key.length; i++) { h ^= key.charCodeAt(i); h = Math.imul(h, 16777619); } return h >>> 0; }
  function clampInt(seed, min, max) { if (max <= min) return min; const span = max - min; return min + (seed % (span + 1)); }
  function ensureWatchLaterNotEmpty() {
    const container = document.getElementById('wl-list');
    if (container && container.children.length === 0) {
      let noItemsText = 'No items saved for later.';
      if (window.i18n && window.i18n.t) {
        const translated = window.i18n.t('watchLater.noItems');
        if (translated !== 'watchLater.noItems') {
          noItemsText = translated;
        }
      }
      container.innerHTML = `<div class="alert alert-info">${noItemsText}</div>`;
    }
  }
})();


