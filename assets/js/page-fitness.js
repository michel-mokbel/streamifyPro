/** Page: Fitness (multi-page version) */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Re-initialize when language changes
  window.addEventListener('languageChanged', init);

  async function init() {
    const container = document.getElementById('fitness-content');
    const grid = document.getElementById('fitness-grid');
    const loadingIndicator = container.querySelector('.loading-indicator');
    
    if (!container || !grid) return;

    try {
      // Get current language
      const currentLang = localStorage.getItem('streamify_language') || 'en';
      
      // Fetch fitness data with language parameter
      const response = await fetch(`./api/api.php?route=fitness&lang=${currentLang}`);
      if (!response.ok) throw new Error('Failed to load fitness data');
      const data = await response.json();
      
      // Hide loading indicator
      if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
      }
      
      // Clear grid
      grid.innerHTML = '';
      
      // Render fitness videos
      if (data.videos && data.videos.length > 0) {
        data.videos.forEach(video => {
          grid.appendChild(createFitnessCard(video));
        });
        
        // Generate thumbnails after cards are created
        setTimeout(() => generateThumbnails(), 100);
      } else {
        grid.innerHTML = '<div class="alert alert-info w-100">No fitness videos available.</div>';
      }
      
      // Set up search functionality
      setupSearch(data.videos || []);
      
    } catch (e) {
      console.error('Error loading fitness content:', e);
      container.innerHTML = `<div class="alert alert-danger">Failed to load fitness content: ${e.message}</div>`;
    }
  }

  function createFitnessCard(video) {
    const wrapper = document.createElement('div');
    wrapper.className = 'streaming-card-wrapper';
    wrapper.dataset.videoId = video.id;
    
    // Get current language and use Arabic translations if available
    const currentLang = localStorage.getItem('streamify_language') || 'en';
    const title = currentLang === 'ar' && video.name_ar ? video.name_ar : (video.name || formatTitle(video.filename));
    
    // Category logic: Use Arabic category if in Arabic, otherwise use English category
    // Note: category_en and category_ar are only available in fitness-ar.json
    let category = 'Fitness'; // Default fallback
    if (currentLang === 'ar' && video.category_ar) {
      category = video.category_ar;
    } else if (video.category_en) {
      category = video.category_en;
    }
    
    // Create placeholder first, then generate thumbnail
    const placeholderUrl = window.PLACEHOLDER_IMAGE || 'https://dummyimage.com/640x360/3182ff/ffffff.png&text=Fitness+Video';
    
    const fitnessText = window.i18n?.t('sidebar.fitness') || 'Fitness';
    const workoutText = window.i18n?.t('fitness.workoutVideo') || 'Workout Video';
    const favoriteText = window.i18n?.t('streaming.favorite') || 'Add to Favorites';
    const watchLaterText = window.i18n?.t('streaming.watchLater') || 'Watch Later';
    
    wrapper.innerHTML = `
      <div class="card shadow-sm h-100">
        <div class="position-relative">
          <img class="card-img-top lazy-image fitness-thumbnail" 
               data-video-url="${video.url}"
               data-video-id="${video.id}"
               src="${placeholderUrl}" 
               alt="${title}" 
               style="height: 180px; object-fit: cover;" />

          <div class="thumb-play">
            <i class="bi bi-play-fill fs-4"></i>
          </div>
        </div>
        <div class="card-body p-3">
          <h5 class="card-title fs-6 text-truncate">${title}</h5>
          <p class="card-text small text-muted mb-1">${category}</p>

        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center p-3">
          <div class="d-flex align-items-center">
  
          </div>
          <div class="d-flex">
            <button class="action-icon me-2 favorite-btn" title="${favoriteText}">
              <i class="bi bi-heart"></i>
            </button>
            <button class="action-icon watch-later-btn" title="${watchLaterText}">
              <i class="bi bi-clock"></i>
            </button>
          </div>
        </div>
      </div>`;
    
    // Favorite button functionality
    const favBtn = wrapper.querySelector('.favorite-btn');
    const favIcon = favBtn.querySelector('i');
    
    function setFavUI(active) {
      favBtn.classList.toggle('text-danger', active);
      favIcon.classList.toggle('bi-heart-fill', active);
      favIcon.classList.toggle('bi-heart', !active);
    }
    
    // Check if already favorited
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.favorites);
        const active = Array.isArray(list) && list.some(x => String(x.id) === String(video.id) && x.type === 'fitness');
        setFavUI(active);
      }
    } catch (e) {}
    
    favBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleFavorite === 'function') {
        // Don't save base64 thumbnail (too large for cookies), use placeholder
        // Thumbnails will be regenerated on favorites page
        toggleFavorite('fitness', {
          Id: video.id,
          Title: title,
          Thumbnail: placeholderUrl,
          Content: video.url
        });
        const nowActive = !favIcon.classList.contains('bi-heart-fill');
        setFavUI(nowActive);
      }
    });
    
    // Watch later button functionality
    const wlBtn = wrapper.querySelector('.watch-later-btn');
    const wlIcon = wlBtn.querySelector('i');
    
    function setWlUI(active) {
      wlBtn.classList.toggle('active', active);
      wlIcon.classList.toggle('bi-clock-fill', active);
      wlIcon.classList.toggle('bi-clock', !active);
    }
    
    // Check if already in watch later
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.watchLater);
        const active = Array.isArray(list) && list.some(x => String(x.id) === String(video.id) && x.type === 'fitness');
        setWlUI(active);
      }
    } catch (e) {}
    
    wlBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleWatchLater === 'function') {
        // Don't save base64 thumbnail (too large for cookies), use placeholder
        // Thumbnails will be regenerated on watch later page
        toggleWatchLater('fitness', {
          Id: video.id,
          Title: title,
          Thumbnail: placeholderUrl,
          Content: video.url
        });
        const nowActive = !wlIcon.classList.contains('bi-clock-fill');
        setWlUI(nowActive);
      }
    });
    
    // Card click - navigate to detail
    wrapper.querySelector('.card').addEventListener('click', (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      window.location.href = `fitness-detail.php?id=${video.id}`;
    });
    
    return wrapper;
  }

  function formatTitle(filename) {
    // Remove file extension
    let title = filename.replace(/\.[^/.]+$/, '');
    
    // Remove 'copy_' prefix if present
    title = title.replace(/^copy_/i, '');
    
    // Replace underscores and hyphens with spaces
    title = title.replace(/[_-]/g, ' ');
    
    // Remove UUID-like patterns
    title = title.replace(/[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}/gi, '');
    
    // Clean up extra spaces
    title = title.trim().replace(/\s+/g, ' ');
    
    // If title is empty after cleaning, use a default
    if (!title) {
      title = `Fitness Video ${filename.substr(0, 8)}`;
    }
    
    // Capitalize first letter of each word
    title = title.replace(/\b\w/g, l => l.toUpperCase());
    
    return title;
  }

  function setupSearch(videos) {
    const searchInput = document.getElementById('fitnessSearch');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', (e) => {
      const searchTerm = e.target.value.toLowerCase().trim();
      const cards = document.querySelectorAll('.streaming-card-wrapper');
      
      cards.forEach(card => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const id = card.dataset.videoId || '';
        
        if (!searchTerm || title.includes(searchTerm) || id.includes(searchTerm)) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
      
      // Show message if no results
      const visibleCards = document.querySelectorAll('.streaming-card-wrapper:not([style*="display: none"])');
      let noResultsMsg = document.getElementById('no-results-msg');
      
      if (visibleCards.length === 0 && searchTerm) {
        if (!noResultsMsg) {
          noResultsMsg = document.createElement('div');
          noResultsMsg.id = 'no-results-msg';
          noResultsMsg.className = 'alert alert-info w-100 mt-3';
          noResultsMsg.textContent = 'No fitness videos found matching your search.';
          document.getElementById('fitness-grid').appendChild(noResultsMsg);
        }
      } else if (noResultsMsg) {
        noResultsMsg.remove();
      }
    });
  }

  // Thumbnail generation functions
  function generateThumbnails() {
    const thumbnails = document.querySelectorAll('.fitness-thumbnail[data-video-url]');
    const thumbnailCache = {};
    
    // Process thumbnails in batches to avoid overwhelming the browser
    let index = 0;
    const batchSize = 3;
    
    function processBatch() {
      const batch = Array.from(thumbnails).slice(index, index + batchSize);
      
      batch.forEach(img => {
        const videoUrl = img.dataset.videoUrl;
        const videoId = img.dataset.videoId;
        
        // Generate thumbnail (will check for pre-generated first, then dynamic)
        if (typeof generateVideoThumbnail === 'function') {
          generateVideoThumbnail(videoUrl, (thumbnailUrl) => {
            if (thumbnailUrl && img) {
              img.src = thumbnailUrl;
            }
          }, videoId);
        }
      });
      
      index += batchSize;
      if (index < thumbnails.length) {
        // Process next batch after a short delay
        setTimeout(processBatch, 500);
      }
    }
    
    processBatch();
  }

  // Note: generateVideoThumbnail is provided by core.js
  // It uses server-side thumbnail generation to avoid CORS issues
})();
