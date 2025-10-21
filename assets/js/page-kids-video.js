(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Listen for language changes and reload content
  window.addEventListener('languageChanged', init);

  async function init() {
    const params = new URLSearchParams(window.location.search);
    const channelId = params.get('channel');
    const playlistId = params.get('playlist');
    const videoId = params.get('video');
    if (!channelId || !playlistId || !videoId) { window.location.replace('kids.php'); return; }

    const data = await fetchKids();
    const { channel, playlist, index } = findItems(data, channelId, playlistId, videoId);
    if (!channel || !playlist || index < 0) { document.querySelector('.container').innerHTML = `<div class=\"alert alert-danger mt-4\">Video not found.</div>`; return; }
    render(channel, playlist, index, data);
  }

  async function fetchKids() {
    // Get current language from i18n or localStorage
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    const res = await fetch(`./api/api.php?route=kids&lang=${currentLang}`);
    if (!res.ok) throw new Error('Failed to load kids');
    return res.json();
  }

  function findItems(data, channelId, playlistId, videoId) {
    let channel = null, playlist = null, index = -1;
    (data.channels || []).forEach(ch => {
      if (channel) return;
      if (String(ch.id) !== String(channelId)) return;
      channel = ch;
      (ch.playlists || []).forEach(pl => {
        if (playlist) return;
        if (String(pl.id) !== String(playlistId)) return;
        playlist = pl;
        index = (pl.content || []).findIndex(v => String(v.id) === String(videoId));
      });
    });
    return { channel, playlist, index };
  }

  function render(channel, playlist, currentIndex, data) {
    const video = (playlist.content || [])[currentIndex];
    if (!video) return;
    
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    
    // Use translated fields if available (for Arabic), otherwise use original
    const videoTitle = (currentLang === 'ar' && video.title_ar) ? video.title_ar : video.title;
    const videoDesc = (currentLang === 'ar' && video.description_ar) ? video.description_ar : (video.description || '');
    const playlistName = (currentLang === 'ar' && playlist.name_ar) ? playlist.name_ar : playlist.name;

    // Direct HTML5 video player
    const videoElement = document.getElementById('kv-video');
    const videoSource = document.getElementById('kv-video-source');
    
    // Set poster image
    videoElement.poster = video.imageCropped || video.imageFile || window.PLACEHOLDER_IMAGE;
    
    // Set video source
    const src = video.sourceFile || '';
    videoSource.src = src;
    
    // Reset and load the video
    videoElement.load();
    
    // Add error handling
    videoElement.onerror = () => {
      console.error('Video failed to load:', src);
      // Fallback to a message if video fails
      if (videoElement.parentNode) {
        videoElement.parentNode.innerHTML = `
          <div class="d-flex align-items-center justify-content-center h-100 text-white bg-dark">
            <div class="text-center p-4">
              <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
              <p>Unable to play this video.</p>
              <p class="small text-muted">The video may be unavailable or in an unsupported format.</p>
            </div>
          </div>
        `;
      }
    };

    document.getElementById('kv-title').textContent = videoTitle || '';
    document.getElementById('kv-desc').textContent = videoDesc || '';

    // Sidebar playlist
    const plImg = document.getElementById('kv-pl-img');
    if (playlist.profileImage) { plImg.src = playlist.profileImage; plImg.alt = playlistName; }
    document.getElementById('kv-pl-name').textContent = playlistName || '';
    
    // Use i18n for "videos" text
    const videosText = window.i18n?.t('kids.videos') || 'videos';
    document.getElementById('kv-pl-count').textContent = `${(playlist.content || []).length} ${videosText}`;

    // Related list (random 6 items excluding current)
    const related = document.getElementById('kv-related'); related.innerHTML = '';
    const others = (playlist.content || []).filter((_, i) => i !== currentIndex);
    // Fisher-Yates shuffle for unbiased random order
    for (let i = others.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [others[i], others[j]] = [others[j], others[i]];
    }
    others.slice(0, 6).forEach(v => {
      // Use translated title if available
      const relatedTitle = (currentLang === 'ar' && v.title_ar) ? v.title_ar : (v.title || 'Video');
      
      const a = document.createElement('a'); a.href = `kids-video.php?channel=${encodeURIComponent(channel.id)}&playlist=${encodeURIComponent(playlist.id)}&video=${encodeURIComponent(v.id)}`; a.className = 'text-decoration-none related-game-item';
      a.innerHTML = `<div class=\"d-flex mb-3\"><div class=\"flex-shrink-0 position-relative\" style=\"width: 100px;\"><img src=\"${v.imageCropped || v.imageFile || window.PLACEHOLDER_IMAGE}\" alt=\"\" class=\"rounded\" style=\"width: 100px; height: 56px; object-fit: cover;\"><div class=\"thumb-play\"><i class=\"bi bi-play-fill fs-6\"></i></div></div><div class=\"flex-grow-1 ms-3\"><div class=\"d-flex flex-column justify-content-between h-100\"><h6 class=\"mb-1\">${relatedTitle}</h6></div></div></div>`;
      related.appendChild(a);
    });

    // Favorite and Watch Later buttons
    const favBtn = document.querySelector('.favorite-btn');
    const wlBtn = document.querySelector('.watch-later-btn');
    
    if (favBtn && wlBtn) {
      const favIcon = favBtn.querySelector('i');
      const wlIcon = wlBtn.querySelector('i');
      
      function setFavUI(active) {
        favBtn.classList.toggle('text-danger', active);
        favIcon.classList.toggle('bi-heart-fill', active);
        favIcon.classList.toggle('bi-heart', !active);
      }
      
      function setWlUI(active) {
        wlBtn.classList.toggle('active', active);
        wlIcon.classList.toggle('bi-clock-fill', active);
        wlIcon.classList.toggle('bi-clock', !active);
      }
      
      // Check initial state
      if (typeof window.readCookieList === 'function' && window.COOKIE_KEYS) {
        const favList = window.readCookieList(window.COOKIE_KEYS.favorites);
        const wlList = window.readCookieList(window.COOKIE_KEYS.watchLater);
        
        const videoId = String(video.id);
        const isFav = Array.isArray(favList) && favList.some(x => String(x.id) === videoId && x.type === 'kids');
        const isWl = Array.isArray(wlList) && wlList.some(x => String(x.id) === videoId && x.type === 'kids');
        
        setFavUI(isFav);
        setWlUI(isWl);
      }
      
      // Favorite button click
      favBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof window.toggleFavorite === 'function') {
          window.toggleFavorite('kids', {
            id: video.id,
            title: video.title,
            imageFile: video.imageFile || video.imageCropped || window.PLACEHOLDER_IMAGE,
            sourceFile: video.sourceFile || ''
          });
          const nowActive = !favIcon.classList.contains('bi-heart-fill');
          setFavUI(nowActive);
          
          // Show feedback
          const addedText = window.i18n?.t('videoDetail.addedToFavorites') || 'Added to Favorites';
          const removedText = window.i18n?.t('videoDetail.removedFromFavorites') || 'Removed from Favorites';
          showToast(nowActive ? addedText : removedText);
        }
      });
      
      // Watch Later button click
      wlBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof window.toggleWatchLater === 'function') {
          window.toggleWatchLater('kids', {
            id: video.id,
            title: video.title,
            imageFile: video.imageFile || video.imageCropped || window.PLACEHOLDER_IMAGE,
            sourceFile: video.sourceFile || ''
          });
          const nowActive = !wlIcon.classList.contains('bi-clock-fill');
          setWlUI(nowActive);
          
          // Show feedback
          const addedWLText = window.i18n?.t('videoDetail.addedToWatchLater') || 'Added to Watch Later';
          const removedWLText = window.i18n?.t('videoDetail.removedFromWatchLater') || 'Removed from Watch Later';
          showToast(nowActive ? addedWLText : removedWLText);
        }
      });
    }

    // Prev/Next
    // Note: CSS already swaps the visual position of buttons in RTL
    // So we keep the functionality the same - kv-prev always goes back, kv-next always goes forward
    document.getElementById('kv-prev').onclick = () => navigate(-1);
    document.getElementById('kv-next').onclick = () => navigate(1);

    function navigate(offset) {
      let ni = currentIndex + offset;
      let np = playlist;
      let nc = channel;
      if (ni < 0) {
        // Move to previous playlist if exists
        const playlists = nc.playlists || [];
        const pIndex = playlists.findIndex(p => p.id === np.id);
        if (pIndex > 0) {
          np = playlists[pIndex - 1];
          ni = (np.content || []).length - 1;
        } else return;
      } else if (ni >= (np.content || []).length) {
        const playlists = nc.playlists || [];
        const pIndex = playlists.findIndex(p => p.id === np.id);
        if (pIndex < playlists.length - 1) {
          np = playlists[pIndex + 1];
          ni = 0;
        } else return;
      }
      const nextVideo = (np.content || [])[ni];
      if (!nextVideo) return;
      history.replaceState(null, '', `kids-video.php?channel=${encodeURIComponent(nc.id)}&playlist=${encodeURIComponent(np.id)}&video=${encodeURIComponent(nextVideo.id)}`);
      render(nc, np, ni, data);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Back link to channel
    const back = document.getElementById('kv-back');
    if (back) {
      back.href = `kids-channel.php?id=${encodeURIComponent(channel.id)}`;
      back.innerHTML = `<i class=\"bi bi-arrow-left me-2\"></i> Back to ${channel.name}`;
    }
  }

  // Toast notification function
  function showToast(message) {
    // Remove any existing toast
    const existingToast = document.querySelector('.kids-video-toast');
    if (existingToast) {
      existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'kids-video-toast';
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(33, 37, 41, 0.95);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      animation: slideIn 0.3s ease-out;
      font-size: 14px;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
      toast.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
})();


