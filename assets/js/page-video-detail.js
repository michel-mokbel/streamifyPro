(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Re-initialize when language changes
  window.addEventListener('languageChanged', init);

  async function init() {
    // console.log('Video Detail - Starting init...');
    const params = new URLSearchParams(window.location.search);
    const targetId = params.get('id');
    const targetCategoryName = params.get('category');

    // console.log('Video Detail - Target ID:', targetId);
    // console.log('Video Detail - Target Category:', targetCategoryName);

    if (!targetId) {
      // console.log('Video Detail - No target ID, redirecting to streaming.php');
      window.location.replace('streaming.php');
      return;
    }

    // Get current language
    const currentLang = localStorage.getItem('streamify_language') || 'en';
   // console.log('Video Detail - Current language:', currentLang);
    
    try {
      // console.log('Video Detail - Fetching data...');
      const data = await fetchData('streaming', currentLang);
      // console.log('Video Detail - Data fetched successfully:', data);

      let foundCategory = null;
      let foundVideo = null;
      let categoryIndex = -1;
      let videoIndex = -1;
      
      // console.log('Video Detail - Searching for video with ID:', targetId);
      // console.log('Video Detail - Data structure:', data);
      
      (data.Content || []).forEach(group => {
        (group.Videos || []).forEach((cat, ci) => {
          if (foundVideo) return;
          (cat.Content || []).forEach((v, vi) => {
            if (foundVideo) return;
            // console.log('Video Detail - Checking video:', v.Id || v.ID, 'against target:', targetId);
            if (String(v.Id || v.ID) === String(targetId)) {
              foundCategory = cat; foundVideo = v; categoryIndex = ci; videoIndex = vi;
              // console.log('Video Detail - Found video!', foundVideo);
            }
          });
        });
      });

      if (!foundVideo) {
        // console.log('Video Detail - Video not found');
        document.querySelector('.container').innerHTML = `<div class=\"alert alert-danger mt-4\">Video not found.</div>`;
        return;
      }

      // console.log('Video Detail - Rendering video...');
      render(foundVideo, foundCategory, { categoryIndex, videoIndex, data });
      
    } catch (error) {
      console.error('Video Detail - Error:', error);
      document.querySelector('.container').innerHTML = `<div class=\"alert alert-danger mt-4\">Error loading video: ${error.message}</div>`;
    }
  }

  async function render(video, category, ctx) {
    // console.log('Rendering video:', video);
    
    // Get current language and use Arabic translations if available
    const currentLang = localStorage.getItem('streamify_language') || 'en';
    const videoTitle = currentLang === 'ar' && video.title_ar ? video.title_ar : video.Title;
    const videoDesc = currentLang === 'ar' && video.description_ar ? video.description_ar : video.Description;
    
    document.getElementById('vd-title').textContent = videoTitle || 'Untitled';
    document.getElementById('vd-desc').textContent = videoDesc || '';
    
    // Direct HTML5 video player
    const videoElement = document.getElementById('vd-video');
    const videoSource = document.getElementById('vd-video-source');
    
    // Set poster image
    videoElement.poster = video.Thumbnail_Large || video.Thumbnail || window.PLACEHOLDER_IMAGE;
    
    // Fetch video URL from API and initialize HLS player
    const videoId = video.Id || video.ID;
    // console.log('Fetching video URL for ID:', videoId);
    
    let videoUrl = '';
    
    try {
      videoUrl = await window.fetchStreamingVideoUrl(videoId);
      // console.log('Fetched video URL:', videoUrl);
    } catch (error) {
      console.error('Error fetching video URL:', error);
      // Fallback to Content field if API fails
      videoUrl = video.Content || '';
      // console.log('Using fallback Content URL:', videoUrl);
    }
    
    // Initialize HLS player with the fetched URL
    if (videoUrl) {
      // console.log('Initializing player with URL:', videoUrl);
      window.initHlsPlayer(videoElement, videoUrl);
    } else {
      console.error('No video URL available');
    }
    
    // Add error handling
    videoElement.onerror = () => {
      console.error('Video failed to load:', videoUrl);
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

    // Duration (seconds to h/m or m/s)
    const d = parseInt(video.Duration, 10);
    document.getElementById('vd-duration').textContent = isNaN(d) ? '--' : formatDuration(d);
    const vm = getSyntheticVideoMetrics(video);
    document.getElementById('vd-rating').textContent = vm.rating ? `${vm.rating.toFixed(1)} / 5` : 'Not rated';
    document.getElementById('vd-views').textContent = fmtNumber(vm.views);

    // Prev/Next controls
    const { data, categoryIndex, videoIndex } = ctx;
    const prevBtn = document.getElementById('vd-prev');
    const nextBtn = document.getElementById('vd-next');

    function getCategory(ci) {
      const groups = data.Content || [];
      for (const g of groups) {
        if (g.Videos && g.Videos[ci]) return g.Videos[ci];
        ci -= (g.Videos || []).length;
      }
      return null;
    }

    function findFlatCategories() {
      const out = [];
      (data.Content || []).forEach(gr => (gr.Videos || []).forEach(c => out.push(c)));
      return out;
    }

    const categoriesFlat = findFlatCategories();

    function navigate(offset) {
      const cat = categoriesFlat[categoryIndex];
      if (!cat) return;
      let ni = videoIndex + offset;
      let nc = categoryIndex;
      // move across categories if needed
      while (ni < 0 && nc > 0) { nc--; ni = (categoriesFlat[nc].Content || []).length - 1; }
      while (ni >= (categoriesFlat[nc].Content || []).length && nc < categoriesFlat.length - 1) { ni = 0; nc++; }
      const nextVideo = (categoriesFlat[nc].Content || [])[ni];
      if (!nextVideo) return;
      // Update URL without full reload
      history.replaceState(null, '', `video-detail.php?id=${encodeURIComponent(nextVideo.Id || nextVideo.ID)}&category=${encodeURIComponent(categoriesFlat[nc].Name || '')}`);
      render(nextVideo, categoriesFlat[nc], { data, categoryIndex: nc, videoIndex: ni });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Note: CSS already swaps the visual position of buttons in RTL
    // So we keep the functionality the same - prev always goes back, next always goes forward
    prevBtn.onclick = () => navigate(-1);
    nextBtn.onclick = () => navigate(1);

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
        
        const videoId = String(video.Id || video.ID);
        const isFav = Array.isArray(favList) && favList.some(x => String(x.id) === videoId && x.type === 'streaming');
        const isWl = Array.isArray(wlList) && wlList.some(x => String(x.id) === videoId && x.type === 'streaming');
        
        setFavUI(isFav);
        setWlUI(isWl);
      }
      
      // Favorite button click
      favBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof window.toggleFavorite === 'function') {
          window.toggleFavorite('streaming', {
            Id: video.Id || video.ID,
            Title: video.Title,
            Thumbnail: video.Thumbnail || video.Thumbnail_Large || window.PLACEHOLDER_IMAGE,
            Content: video.Content || ''
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
          window.toggleWatchLater('streaming', {
            Id: video.Id || video.ID,
            Title: video.Title,
            Thumbnail: video.Thumbnail || video.Thumbnail_Large || window.PLACEHOLDER_IMAGE,
            Content: video.Content || ''
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

    // Sidebar
    // Get current language and use Arabic translations if available
    const categoryName = currentLang === 'ar' && category?.name_ar ? category.name_ar : category?.Name;
    
    document.getElementById('vd-cat-name').textContent = categoryName || window.i18n?.t('videoDetail.category') || 'Category';
    const img = document.getElementById('vd-cat-img');
    if (category?.Icon) { img.src = category.Icon; img.alt = categoryName; } else { img.src = window.PLACEHOLDER_IMAGE; }
    const count = (category?.Content || []).length; 
    const videosText = count === 1 ? (window.i18n?.t('streaming.view') || 'video') : (window.i18n?.t('streaming.views') || 'videos');
    document.getElementById('vd-cat-count').textContent = `${count} ${videosText}`;
    
    document.getElementById('vd-related-title').textContent = `${window.i18n?.t('videoDetail.moreVideos') || 'More from'} ${categoryName || ''}`;

    const related = document.getElementById('vd-related'); related.innerHTML = '';
    (category?.Content || []).filter(v => (v.Id || v.ID) !== (video.Id || video.ID)).slice(0, 5).forEach(rv => {
      const a = document.createElement('a'); a.href = `video-detail.php?id=${encodeURIComponent(rv.Id || rv.ID)}&category=${encodeURIComponent(category.Name)}`; a.className = 'text-decoration-none related-game-item';
      const rm = getSyntheticVideoMetrics(rv);
      const viewsText = window.i18n?.t('streaming.views') || 'views';
      
      // Get related video title (Arabic if available)
      const relatedVideoTitle = currentLang === 'ar' && rv.title_ar ? rv.title_ar : rv.Title;
      
      a.innerHTML = `<div class=\"d-flex mb-3\"><div class=\"flex-shrink-0 position-relative\" style=\"width: 100px;\"><img src=\"${rv.Thumbnail || rv.Thumbnail_Large || window.PLACEHOLDER_IMAGE}\" alt=\"\" class=\"rounded\" style=\"width: 100px; height: 56px; object-fit: cover;\"><div class=\"thumb-play\"><i class=\"bi bi-play-fill fs-6\"></i></div></div><div class=\"flex-grow-1 ms-3\"><div class=\"d-flex flex-column justify-content-between h-100\"><h6 class=\"mb-1\">${relatedVideoTitle || 'Video'}</h6><p class=\"mb-0 text-muted small\">${fmtNumber(rm.views)} ${viewsText}</p></div></div></div>`;
      related.appendChild(a);
    });
  }

  function formatDuration(sec) {
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return `${h}h ${m}m`;
    if (m > 0) return `${m}m`;
    return `${s}s`;
  }

  // Toast notification function
  function showToast(message) {
    // Remove any existing toast
    const existingToast = document.querySelector('.video-detail-toast');
    if (existingToast) {
      existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'video-detail-toast';
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

// Synthetic metrics helpers (match streaming cards)
function getSyntheticVideoMetrics(video) {
  const key = String(video.Id || video.ID || video.Title || Math.random());
  const views = clampInt(randFromKey(key + ':views'), 5000, 3000000);
  const ratingRaw = 3.6 + (randFromKey(key + ':rating') / 4294967295) * (4.9 - 3.6);
  const rating = Math.round(ratingRaw * 10) / 10;
  return { views, rating };
}

function randFromKey(key) {
  let h = 2166136261 >>> 0;
  for (let i = 0; i < key.length; i++) { h ^= key.charCodeAt(i); h = Math.imul(h, 16777619); }
  return h >>> 0;
}

function clampInt(seed, min, max) {
  if (max <= min) return min;
  const span = max - min;
  return min + (seed % (span + 1));
}

function fmtNumber(num) {
  try {
    if (typeof formatNumber === 'function') return formatNumber(num);
  } catch (e) {}
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  return String(num);
}


