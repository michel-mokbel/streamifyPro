/** Page: Fitness Detail */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Re-initialize when language changes
  window.addEventListener('languageChanged', init);

  async function init() {
    const params = new URLSearchParams(window.location.search);
    const videoId = parseInt(params.get('id'), 10);
    
    if (!videoId) {
      window.location.replace('fitness.php');
      return;
    }

    try {
      // Get current language
      const currentLang = localStorage.getItem('streamify_language') || 'en';
      
      // Fetch fitness data with language parameter
      const response = await fetch(`./api/api.php?route=fitness&lang=${currentLang}`);
      if (!response.ok) throw new Error('Failed to load fitness data');
      const data = await response.json();
      
      // Find the current video and its index
      const videos = data.videos || [];
      const currentIndex = videos.findIndex(v => v.id === videoId);
      
      if (currentIndex === -1) {
        document.querySelector('.container').innerHTML = 
          '<div class="alert alert-danger mt-4">Fitness video not found.</div>';
        return;
      }
      
      const currentVideo = videos[currentIndex];
      
      // Render the video with navigation context
      render(currentVideo, videos, currentIndex);
      
    } catch (e) {
      console.error('Error loading fitness video:', e);
      document.querySelector('.container').innerHTML = 
        `<div class="alert alert-danger mt-4">Failed to load fitness video: ${e.message}</div>`;
    }
  }

  function render(video, allVideos, currentIndex) {
    // Get current language and use Arabic translations if available
    const currentLang = localStorage.getItem('streamify_language') || 'en';
    const title = currentLang === 'ar' && video.name_ar ? video.name_ar : (video.name || formatTitle(video.filename));
    document.getElementById('fitness-title').textContent = title;
    
    // Category logic: Use Arabic category if in Arabic, otherwise use English category
    // Note: category_en and category_ar are only available in fitness-ar.json
    let category = 'Fitness'; // Default fallback
    if (currentLang === 'ar' && video.category_ar) {
      category = video.category_ar;
    } else if (video.category_en) {
      category = video.category_en;
    }
    document.getElementById('fitness-category').textContent = category;
    
    // Update description with Arabic translation if available
    const description = currentLang === 'ar' && video.description_ar ? video.description_ar : video.description;
    document.getElementById('fitness-description').textContent = description || 'No description available.';
    
    // Update tips with Arabic translation if available
    const tips = currentLang === 'ar' && video.tips_ar ? video.tips_ar : video.tips;
    document.getElementById('fitness-tips').textContent = tips || 'No tips available.';
    
    // Update sets and reps with Arabic translation if available
    const setsReps = currentLang === 'ar' && video.sets_reps_ar ? video.sets_reps_ar : video.sets_reps;
    document.getElementById('fitness-sets-reps').textContent = setsReps || 'No sets and reps information available.';
    
    // Setup video player
    const videoElement = document.getElementById('fitness-video');
    const videoSource = document.getElementById('fitness-video-source');
    const videoSourceMp4 = document.getElementById('fitness-video-source-mp4');
    
    // Try to use pre-generated thumbnail first
    const preGeneratedThumb = `./assets/thumbnails/${video.id}.jpg`;
    
    // Test if pre-generated thumbnail exists
    const testImg = new Image();
    testImg.onload = function() {
      // Pre-generated thumbnail exists, use it
      videoElement.poster = preGeneratedThumb;
    };
    testImg.onerror = function() {
      // Pre-generated doesn't exist, use dynamic generation
      videoElement.poster = window.PLACEHOLDER_IMAGE || 
        'https://dummyimage.com/1280x720/3182ff/ffffff.png&text=Fitness+Video';
      
      if (typeof generateVideoThumbnail === 'function') {
        generateVideoThumbnail(video.url, (thumbnailUrl) => {
          if (thumbnailUrl) {
            videoElement.poster = thumbnailUrl;
          }
        }, video.id);
      }
    };
    testImg.src = preGeneratedThumb;
    
    // Set video sources - .mov as primary, try to convert URL for MP4 fallback
    videoSource.src = video.url;
    // Some browsers may not support .mov, so we'll set the same URL for MP4 source
    // In production, you'd want to have actual MP4 versions
    videoSourceMp4.src = video.url;
    
    // Load the video
    videoElement.load();
    
    // Add error handling
    videoElement.onerror = () => {
      console.error('Video failed to load:', video.url);
      if (videoElement.parentNode) {
        videoElement.parentNode.innerHTML = `
          <div class="d-flex align-items-center justify-content-center h-100 text-white bg-dark">
            <div class="text-center p-4">
              <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
              <p>Unable to play this fitness video.</p>
              <p class="small text-muted">The video may require conversion to a supported format.</p>
              <a href="${video.url}" target="_blank" class="btn btn-primary mt-3">
                <i class="bi bi-download me-2"></i>Download Video
              </a>
            </div>
          </div>
        `;
      }
    };
    
    // Setup Previous/Next navigation
    // Note: CSS already swaps the visual position of buttons in RTL
    // So we keep the functionality the same - prevBtn always goes back, nextBtn always goes forward
    const prevBtn = document.getElementById('fitness-prev');
    const nextBtn = document.getElementById('fitness-next');
    
    // Previous button (goes to previous video)
    if (prevBtn) {
      if (currentIndex > 0) {
        prevBtn.disabled = false;
        prevBtn.onclick = () => {
          const prevVideo = allVideos[currentIndex - 1];
          if (prevVideo) {
            // Update URL and re-render without full page reload
            history.replaceState(null, '', `fitness-detail.php?id=${prevVideo.id}`);
            render(prevVideo, allVideos, currentIndex - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        };
      } else {
        prevBtn.disabled = true;
      }
    }
    
    // Next button (goes to next video)
    if (nextBtn) {
      if (currentIndex < allVideos.length - 1) {
        nextBtn.disabled = false;
        nextBtn.onclick = () => {
          const nextVideo = allVideos[currentIndex + 1];
          if (nextVideo) {
            // Update URL and re-render without full page reload
            history.replaceState(null, '', `fitness-detail.php?id=${nextVideo.id}`);
            render(nextVideo, allVideos, currentIndex + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        };
      } else {
        nextBtn.disabled = true;
      }
    }
    
    // Setup favorite button
    const favBtn = document.querySelector('.favorite-btn');
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
        const active = Array.isArray(list) && 
          list.some(x => String(x.id) === String(video.id) && x.type === 'fitness');
        setFavUI(active);
      }
    } catch (e) {}
    
    favBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof toggleFavorite === 'function') {
        // Use placeholder instead of base64 poster (too large for cookies)
        const placeholderUrl = window.PLACEHOLDER_IMAGE || 'https://dummyimage.com/640x360/3182ff/ffffff.png&text=Fitness';
        toggleFavorite('fitness', {
          Id: video.id,
          Title: title,
          Thumbnail: placeholderUrl,
          Content: video.url
        });
        const nowActive = !favIcon.classList.contains('bi-heart-fill');
        setFavUI(nowActive);
        
        // Show feedback
        const addedText = window.i18n?.t('videoDetail.addedToFavorites') || 'Added to Favorites';
        const removedText = window.i18n?.t('videoDetail.removedFromFavorites') || 'Removed from Favorites';
        showToast(nowActive ? addedText : removedText);
      }
    });
    
    // Setup watch later button
    const wlBtn = document.querySelector('.watch-later-btn');
    const wlIcon = wlBtn.querySelector('i');
    
    function setWlUI(active) {
      wlBtn.classList.toggle('active', active);
      wlIcon.classList.toggle('bi-clock-fill', active);
      wlIcon.classList.toggle('bi-clock', !active);
    }
    
    // Check if in watch later
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.watchLater);
        const active = Array.isArray(list) && 
          list.some(x => String(x.id) === String(video.id) && x.type === 'fitness');
        setWlUI(active);
      }
    } catch (e) {}
    
    wlBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof toggleWatchLater === 'function') {
        // Use placeholder instead of base64 poster (too large for cookies)
        const placeholderUrl = window.PLACEHOLDER_IMAGE || 'https://dummyimage.com/640x360/3182ff/ffffff.png&text=Fitness';
        toggleWatchLater('fitness', {
          Id: video.id,
          Title: title,
          Thumbnail: placeholderUrl,
          Content: video.url
        });
        const nowActive = !wlIcon.classList.contains('bi-clock-fill');
        setWlUI(nowActive);
        
        // Show feedback
        const addedWLText = window.i18n?.t('videoDetail.addedToWatchLater') || 'Added to Watch Later';
        const removedWLText = window.i18n?.t('videoDetail.removedFromWatchLater') || 'Removed from Watch Later';
        showToast(nowActive ? addedWLText : removedWLText);
      }
    });
    
    // Load related videos
    loadRelatedVideos(video, allVideos, currentIndex);
  }

  function loadRelatedVideos(currentVideo, allVideos, currentIndex) {
    const related = document.getElementById('fitness-related');
    if (!related) return;
    
    // Get other videos (exclude current)
    const otherVideos = allVideos.filter(v => v.id !== currentVideo.id);
    
    // Shuffle and take first 5
    const shuffled = [...otherVideos].sort(() => Math.random() - 0.5);
    const relatedVideos = shuffled.slice(0, 5);
    
    related.innerHTML = '';
    
    if (relatedVideos.length === 0) {
      related.innerHTML = '<p class="text-muted small">No other fitness videos available.</p>';
      return;
    }
    
    relatedVideos.forEach(video => {
      // Get current language and use Arabic translations if available
      const currentLang = localStorage.getItem('streamify_language') || 'en';
      const title = currentLang === 'ar' && video.name_ar ? video.name_ar : (video.name || formatTitle(video.filename));
      
      const a = document.createElement('a');
      a.href = `fitness-detail.php?id=${video.id}`;
      a.className = 'text-decoration-none d-block mb-3';
      
      // Use pre-generated thumbnail
      const preGeneratedThumb = `./assets/thumbnails/${video.id}.jpg`;
      const placeholderUrl = window.PLACEHOLDER_IMAGE || 'https://dummyimage.com/640x360/3182ff/ffffff.png&text=Fitness';
      
      a.innerHTML = `
        <div class="d-flex">
          <div class="flex-shrink-0 position-relative" style="width: 100px;">
            <img src="${preGeneratedThumb}" 
                 onerror="this.src='${placeholderUrl}'" 
                 alt="${title}" 
                 class="rounded" 
                 style="width: 100px; height: 56px; object-fit: cover;">
            <div class="thumb-play">
              <i class="bi bi-play-fill fs-6"></i>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h6 class="mb-1 text-truncate">${title}</h6>
            <p class="mb-0 text-muted small">Video #${video.id}</p>
          </div>
        </div>
      `;
      
      related.appendChild(a);
    });
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

  // Note: generateVideoThumbnail is provided by core.js
  // It uses server-side thumbnail generation to avoid CORS issues

  // Toast notification function
  function showToast(message) {
    // Remove any existing toast
    const existingToast = document.querySelector('.fitness-toast');
    if (existingToast) {
      existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'fitness-toast';
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(33, 37, 41, 0.95);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      animation: slideInRight 0.3s ease;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    `;
    
    // Add icon based on message
    const icon = message.includes('Added') ? '✓' : '✗';
    toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> ${message}`;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideInRight {
        from {
          transform: translateX(100%);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
    `;
    document.head.appendChild(style);
    
    // Remove after 3 seconds
    setTimeout(() => {
      toast.style.animation = 'slideOutRight 0.3s ease';
      toast.style.animationFillMode = 'forwards';
      setTimeout(() => {
        toast.remove();
        style.remove();
      }, 300);
    }, 3000);
  }
})();
