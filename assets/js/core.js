/**
 * Core utilities for the multi‑page version (no SPA)
 */
(function () {
  'use strict';
  
  // Set up sidebar toggle functionality for mobile
  document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-collapsed');
      });
      
      // Close sidebar when clicking outside of it on mobile
      document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = sidebarToggle.contains(event.target);
        const isSidebarOpen = document.body.classList.contains('sidebar-collapsed');
        
        if (isSidebarOpen && !isClickInsideSidebar && !isClickOnToggle && window.innerWidth < 768) {
          document.body.classList.remove('sidebar-collapsed');
        }
      });
    }
  });

  // Constants
  const API_BASE = './api/api.php?route=';
  const PLACEHOLDER_IMAGE = './assets/img/logo.png';

  // Expose globally
  window.API_BASE = window.API_BASE || API_BASE;
  window.PLACEHOLDER_IMAGE = window.PLACEHOLDER_IMAGE || PLACEHOLDER_IMAGE;

  // Fetch helper
  async function fetchData(route, lang) {
    // Get language from parameter or localStorage
    const currentLang = lang || localStorage.getItem('streamify_language') || 'en';
    const url = `${API_BASE}${route}&lang=${currentLang}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`API error: ${response.status}`);
    }
    return response.json();
  }

  window.fetchData = window.fetchData || fetchData;

  // Lightweight lazy image loader
  const lazyObserver = 'IntersectionObserver' in window ? new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const img = entry.target;
      const src = img.getAttribute('data-src');
      if (src) {
        img.src = src;
        img.removeAttribute('data-src');
      }
      obs.unobserve(img);
    });
  }, { rootMargin: '200px 0px', threshold: 0.01 }) : null;

  function applyLazyLoading(img, src, alt) {
    if (!img) return;
    img.alt = alt || '';
    if (lazyObserver) {
      img.setAttribute('data-src', src || PLACEHOLDER_IMAGE);
      img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
      img.classList.add('lazy-image');
      lazyObserver.observe(img);
    } else {
      img.src = src || PLACEHOLDER_IMAGE;
    }
    img.onerror = () => { img.src = PLACEHOLDER_IMAGE; };
  }

  function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return (num || 0).toString();
  }

  window.applyLazyLoading = window.applyLazyLoading || applyLazyLoading;
  window.formatNumber = window.formatNumber || formatNumber;

  // Minimal preferences (favorites/watch-later) using cookies (simple JSON)
  const SUFFIX = (typeof window !== 'undefined' && window.USER_STORAGE_SUFFIX) ? window.USER_STORAGE_SUFFIX : '_guest';
  const STORAGE_KEYS = {
    favorites: 'mh_favorites' + SUFFIX,
    watchLater: 'mh_watchlater' + SUFFIX
  };

  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }
  function setCookie(name, value, days) {
    const expires = new Date(Date.now() + (days || 365) * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
  }
  function readList(key) {
    try { return JSON.parse(getCookie(key) || '[]'); } catch { return []; }
  }
  function writeList(key, list) {
    try { setCookie(key, JSON.stringify(list), 365); } catch {}
  }

  function toggleFavorite(type, item) {
    const list = readList(STORAGE_KEYS.favorites);
    const id = item.ID || item.Id || item.id;
    const idx = list.findIndex(x => x.id == id && x.type === type);
    if (idx >= 0) {
      list.splice(idx, 1);
    } else {
      list.push({
        id,
        type,
        title: item.Title || item.title || 'Untitled',
        image: item.Thumbnail || item.Thumbnail_Large || item.imageCropped || item.imageFile || PLACEHOLDER_IMAGE,
        url: item.Content || item.sourceFile || item.url || ''
      });
    }
    writeList(STORAGE_KEYS.favorites, list);
  }

  function toggleWatchLater(type, item) {
    const list = readList(STORAGE_KEYS.watchLater);
    const id = item.ID || item.Id || item.id;
    const idx = list.findIndex(x => x.id == id && x.type === type);
    if (idx >= 0) {
      list.splice(idx, 1);
    } else {
      list.push({
        id,
        type,
        title: item.Title || item.title || 'Untitled',
        image: item.Thumbnail || item.Thumbnail_Large || item.imageCropped || item.imageFile || PLACEHOLDER_IMAGE,
        url: item.Content || item.sourceFile || item.url || ''
      });
    }
    writeList(STORAGE_KEYS.watchLater, list);
  }

  window.toggleFavorite = toggleFavorite;
  window.toggleWatchLater = toggleWatchLater;
  // Expose simple cookie list helpers for pages
  window.readCookieList = readList;
  window.writeCookieList = writeList;
  window.COOKIE_KEYS = STORAGE_KEYS;

  /**
   * Fetch streaming video URL from API
   * @param {string|number} videoId - The video ID
   * @returns {Promise<string>} - The video URL
   */
  async function fetchStreamingVideoUrl(videoId) {
    try {
      const response = await fetch(`https://apiv2.mobileartsme.com/av_geturl?videoId=${videoId}`);
      const data = await response.json();
      
      if (data.error === 0 && data.result) {
        return data.result;
      } else {
        throw new Error(data.result || 'Failed to fetch video URL');
      }
    } catch (error) {
      console.error('Error fetching streaming video URL:', error);
      throw error;
    }
  }

  /**
   * Initialize HLS video player
   * @param {HTMLVideoElement} videoElement - The video element
   * @param {string} videoUrl - The video URL (can be .m3u8 or regular video)
   * @returns {Object|null} - HLS instance or null
   */
  function initHlsPlayer(videoElement, videoUrl) {
    if (!videoUrl) {
      console.error('No video URL provided');
      return null;
    }

    // console.log('Initializing video player with URL:', videoUrl);

    // Check if it's an HLS stream
    if (videoUrl.includes('.m3u8')) {
      // console.log('Detected HLS stream');
      
      // HLS stream - use HLS.js for browsers that don't support it natively
      if (videoElement.canPlayType('application/vnd.apple.mpegurl') || videoElement.canPlayType('application/x-mpegURL')) {
        // Native HLS support (Safari, iOS)
        // console.log('Using native HLS support');
        videoElement.src = videoUrl;
        videoElement.load();
        return null;
      } else if (window.Hls && Hls.isSupported()) {
        // Use HLS.js for other browsers
        // console.log('Using HLS.js for playback');
        
        // Destroy any existing HLS instance
        if (videoElement.hlsInstance) {
          videoElement.hlsInstance.destroy();
        }
        
        const hls = new Hls({
          debug: false,
          enableWorker: true,
          lowLatencyMode: false,
          backBufferLength: 90
        });
        
        hls.loadSource(videoUrl);
        hls.attachMedia(videoElement);
        
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
          // console.log('HLS manifest parsed successfully, ready to play');
        });
        
        hls.on(Hls.Events.ERROR, function(event, data) {
          console.error('HLS error:', data);
          if (data.fatal) {
            switch(data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                // console.log('Fatal network error encountered, trying to recover...');
                hls.startLoad();
                break;
              case Hls.ErrorTypes.MEDIA_ERROR:
                // console.log('Fatal media error encountered, trying to recover...');
                hls.recoverMediaError();
                break;
              default:
                console.error('Fatal error, cannot recover');
                hls.destroy();
                break;
            }
          }
        });
        
        // Store HLS instance on video element for cleanup
        videoElement.hlsInstance = hls;
        
        return hls;
      } else {
        // console.error('HLS is not supported in this browser');
        // Fallback - try direct source
        videoElement.src = videoUrl;
        videoElement.load();
        return null;
      }
    } else {
      // Regular MP4 or other video format
      // console.log('Using standard video playback for:', videoUrl);
      videoElement.src = videoUrl;
      videoElement.load();
      return null;
    }
  }

  // Expose globally
  window.fetchStreamingVideoUrl = fetchStreamingVideoUrl;
  window.initHlsPlayer = initHlsPlayer;

  /**
   * Generate video thumbnail by extracting first frame
   * First checks for pre-generated thumbnail, then falls back to dynamic generation
   */
  function generateVideoThumbnail(videoUrl, callback, videoId) {
    if (!videoUrl || typeof callback !== 'function') {
      console.warn('generateVideoThumbnail: Invalid parameters');
      return;
    }

    // If videoId provided, try to load pre-generated thumbnail first
    if (videoId) {
      const preGeneratedUrl = `./assets/thumbnails/${videoId}.jpg`;
      const testImg = new Image();
      
      testImg.onload = function() {
        // Pre-generated thumbnail exists, use it
        callback(preGeneratedUrl);
      };
      
      testImg.onerror = function() {
        // Pre-generated thumbnail doesn't exist, generate dynamically
        generateDynamicThumbnail(videoUrl, callback);
      };
      
      testImg.src = preGeneratedUrl;
      return;
    }

    // No videoId provided, generate dynamically
    generateDynamicThumbnail(videoUrl, callback);
  }

  /**
   * Generate thumbnail dynamically from video
   */
  function generateDynamicThumbnail(videoUrl, callback) {
    // Check cache first
    const cacheKey = 'thumb_' + btoa(videoUrl).substring(0, 50);
    const cached = localStorage.getItem(cacheKey);
    
    if (cached && cached.startsWith('data:image')) {
      callback(cached);
      return;
    }

    // Create video element
    const video = document.createElement('video');
    video.crossOrigin = 'anonymous';
    video.muted = true;
    video.playsInline = true;
    video.preload = 'metadata';
    
    // Use proxy to bypass CORS
    const proxyUrl = `./api/generate-thumbnail.php?url=${encodeURIComponent(videoUrl)}`;
    
    let thumbnailGenerated = false;
    
    video.addEventListener('loadeddata', function() {
      if (thumbnailGenerated) return;
      
      // Wait a bit for the first frame to be ready
      setTimeout(() => {
        if (thumbnailGenerated) return;
        
        try {
          // Seek to 1 second or 5% of duration for a better frame
          const seekTime = Math.min(1, video.duration * 0.05);
          video.currentTime = seekTime;
        } catch (e) {
          // If seeking fails, capture current frame
          captureThumbnail();
        }
      }, 100);
    });
    
    video.addEventListener('seeked', function() {
      if (!thumbnailGenerated) {
        captureThumbnail();
      }
    });
    
    function captureThumbnail() {
      if (thumbnailGenerated) return;
      thumbnailGenerated = true;
      
      try {
        // Create canvas and draw video frame
        const canvas = document.createElement('canvas');
        canvas.width = 640;
        canvas.height = 360;
        
        const ctx = canvas.getContext('2d');
        
        // Calculate dimensions to maintain aspect ratio
        const videoRatio = video.videoWidth / video.videoHeight;
        const canvasRatio = canvas.width / canvas.height;
        
        let drawWidth = canvas.width;
        let drawHeight = canvas.height;
        let offsetX = 0;
        let offsetY = 0;
        
        if (videoRatio > canvasRatio) {
          drawWidth = canvas.width;
          drawHeight = canvas.width / videoRatio;
          offsetY = (canvas.height - drawHeight) / 2;
        } else {
          drawHeight = canvas.height;
          drawWidth = canvas.height * videoRatio;
          offsetX = (canvas.width - drawWidth) / 2;
        }
        
        // Fill background
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw video frame
        ctx.drawImage(video, offsetX, offsetY, drawWidth, drawHeight);
        
        // Convert to data URL
        const thumbnailDataUrl = canvas.toDataURL('image/jpeg', 0.8);
        
        // Cache and return
        localStorage.setItem(cacheKey, thumbnailDataUrl);
        callback(thumbnailDataUrl);
        
        // Clean up
        video.remove();
      } catch (error) {
        console.warn('Failed to capture thumbnail:', error);
        fallbackThumbnail();
      }
    }
    
    function fallbackThumbnail() {
      // Generate a simple gradient as fallback
      const canvas = document.createElement('canvas');
      canvas.width = 640;
      canvas.height = 360;
      const ctx = canvas.getContext('2d');
      
      // Create gradient based on URL hash
      let hash = 0;
      for (let i = 0; i < videoUrl.length; i++) {
        hash = ((hash << 5) - hash) + videoUrl.charCodeAt(i);
        hash = hash & hash;
      }
      
      const hue = Math.abs(hash % 360);
      const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
      gradient.addColorStop(0, `hsl(${hue}, 70%, 50%)`);
      gradient.addColorStop(1, `hsl(${(hue + 40) % 360}, 70%, 40%)`);
      
      ctx.fillStyle = gradient;
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      
      // Add play icon
      ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
      ctx.beginPath();
      ctx.arc(canvas.width / 2, canvas.height / 2, 50, 0, 2 * Math.PI);
      ctx.fill();
      
      ctx.fillStyle = '#000';
      ctx.beginPath();
      ctx.moveTo(canvas.width / 2 - 15, canvas.height / 2 - 20);
      ctx.lineTo(canvas.width / 2 - 15, canvas.height / 2 + 20);
      ctx.lineTo(canvas.width / 2 + 20, canvas.height / 2);
      ctx.closePath();
      ctx.fill();
      
      const fallbackDataUrl = canvas.toDataURL('image/jpeg', 0.8);
      callback(fallbackDataUrl);
      video.remove();
    }
    
    video.addEventListener('error', function(e) {
      console.warn('Video load error:', e);
      if (!thumbnailGenerated) {
        thumbnailGenerated = true;
        fallbackThumbnail();
      }
    });
    
    // Start loading
    video.src = proxyUrl;
    video.load();
  }

  // Expose globally
  window.generateVideoThumbnail = generateVideoThumbnail;
})();


