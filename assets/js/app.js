/**
 * Streamify Pro App
 * Main JavaScript file for the Streamify Pro dashboard
 */

(function() {
  'use strict';

  // Lazy loading observer
  const lazyImageObserver = 'IntersectionObserver' in window ? new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        const src = img.getAttribute('data-src');
        
        if (src) {
          img.src = src;
          img.removeAttribute('data-src');
        }
        
        observer.unobserve(img);
      }
    });
  }, {
    rootMargin: '200px 0px', // Start loading images when they're 200px from viewport
    threshold: 0.01
  }) : null;

  // Configuration for data paths in JSON files
  const ROUTES = {
    streaming: {
      path: 'Content[].Videos[].Content[]',
      actionLabel: 'Watch',
      imageKeys: ['Thumbnail', 'Thumbnail_Large', 'thumbnail', 'poster', 'image', 'img'],
      urlKeys: ['Content', 'url', 'link', 'href']
    },
    games: {
      path: 'Content[].HTML5[].Content[]',
      actionLabel: 'Play',
      imageKeys: ['Thumbnail', 'Thumbnail_Large', 'thumbnail', 'poster', 'image', 'img'],
      urlKeys: ['Content', 'url', 'link', 'href']
    },
    kids: {
      path: 'channels[].playlists[].content[]',
      actionLabel: 'View',
      imageKeys: ['imageCropped', 'imageFile', 'thumbnail', 'poster', 'image', 'img'],
      urlKeys: ['sourceFile', 'url', 'Content', 'link', 'href']
    },
  };

  // Constants
  const API_BASE = './api/api.php?route=';
  const VIEWS_BASE = './views/';
  const DEFAULT_VIEW = 'home';
  const PLACEHOLDER_IMAGE = './assets/img/logo.png';
  
  // Expose constants to global scope for other modules
  window.API_BASE = API_BASE;
  window.PLACEHOLDER_IMAGE = PLACEHOLDER_IMAGE;

  // App state
  const SUFFIX = (typeof window !== 'undefined' && window.USER_STORAGE_SUFFIX) ? window.USER_STORAGE_SUFFIX : '';
  const FAVORITES_KEY = 'Streamify Pro_favorites' + SUFFIX;
  const WATCHLATER_KEY = 'Streamify Pro_watchlater' + SUFFIX;
  const STATE = {
    currentView: null,
    viewData: {},
    favorites: [],
    watchLater: [],
    theme: 'light'
  };
  
  // Expose STATE to global scope for other modules
  window.STATE = STATE;

  // DOM Elements
  const viewContainer = document.getElementById('view-container');
  const navLinks = document.querySelectorAll('.nav-link');

  /**
   * Initialize the application
   */
  function init() {
    // Load favorites and watch later from localStorage
    loadUserPreferences();
    
    // Set up navigation
    setupNavigation();
    
    // Set up theme
    initTheme();
    
    // Load the initial view based on hash or default
    loadInitialView();
    
    // Listen for hash changes
    window.addEventListener('hashchange', handleHashChange);
  }

  /**
   * Load user preferences from localStorage
   */
  function loadUserPreferences() {
    try {
      // Migrate legacy keys if needed
      const legacyFav = localStorage.getItem('Streamify Pro_favorites');
      const legacyWl = localStorage.getItem('Streamify Pro_watchlater');
      if (!localStorage.getItem(FAVORITES_KEY) && legacyFav) localStorage.setItem(FAVORITES_KEY, legacyFav);
      if (!localStorage.getItem(WATCHLATER_KEY) && legacyWl) localStorage.setItem(WATCHLATER_KEY, legacyWl);

      const favorites = localStorage.getItem(FAVORITES_KEY);
      if (favorites) {
        STATE.favorites = JSON.parse(favorites);
      }
      
      const watchLater = localStorage.getItem(WATCHLATER_KEY);
      if (watchLater) {
        STATE.watchLater = JSON.parse(watchLater);
      }
      
      const theme = localStorage.getItem('Streamify Pro_theme');
      if (theme) {
        STATE.theme = theme;
      }
    } catch (e) {
      console.error('Error loading user preferences:', e);
    }
  }

  /**
   * Save user preferences to localStorage
   */
  function saveUserPreferences() {
    try {
      localStorage.setItem(FAVORITES_KEY, JSON.stringify(STATE.favorites));
      localStorage.setItem(WATCHLATER_KEY, JSON.stringify(STATE.watchLater));
      localStorage.setItem('Streamify Pro_theme', STATE.theme);
    } catch (e) {
      console.error('Error saving user preferences:', e);
    }
  }

  /**
   * Set up navigation event listeners
   */
  function setupNavigation() {
    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const view = link.getAttribute('data-view');
        if (view) {
          navigateToView(view);
        }
      });
    });
  }

  /**
   * Initialize theme based on user preference
   */
  function initTheme() {
    document.body.classList.add(STATE.theme === 'dark' ? 'dark-theme' : 'light-theme');
    
    // Theme toggle can be added here if needed
  }

  /**
   * Load the initial view based on URL hash or default
   */
  function loadInitialView() {
    const hash = window.location.hash.substring(1);
    navigateToView(hash || DEFAULT_VIEW);
  }

  /**
   * Handle hash change events
   */
  function handleHashChange() {
    const hash = window.location.hash.substring(1);
    navigateToView(hash || DEFAULT_VIEW);
  }

  /**
   * Navigate to a specific view
   * @param {string} view - The view name to navigate to
   * @param {object} params - Optional parameters for the view
   */
  function navigateToView(view, params = {}) {
    // Update hash without triggering hashchange event
    const currentHash = window.location.hash.substring(1);
    if (currentHash !== view) {
      history.pushState(null, null, `#${view}`);
    }
    
    // Update active navigation link
    navLinks.forEach(link => {
      const linkView = link.getAttribute('data-view');
      if (linkView === view) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
    
    // Load the view content
    loadView(view, params);
  }
  
  // Expose navigateToView to global scope for other modules
  window.navigateToView = navigateToView;

  /**
   * Load a view's HTML content
   * @param {string} view - The view name to load
   * @param {object} params - Optional parameters for the view
   */
  async function loadView(view, params = {}) {
    try {
      //console.log(`Loading view: ${view}`, params);
      
      // Special case for game-detail view
      if (view === 'game-detail') {
       // console.log('Loading game-detail view...');
        // Make sure we have game data in state
        if (!STATE.viewData.gameDetail) {
          console.error('No game detail data found in state');
          navigateToView('games');
          return;
        }
      }
      
      // Show loading state
      viewContainer.innerHTML = `
        <div class="text-center p-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;
      
      // Fetch the view HTML
      const response = await fetch(`${VIEWS_BASE}${view}.php`);
      if (!response.ok) {
        throw new Error(`Failed to load view: ${response.status}`);
      }
      
      const html = await response.text();
      viewContainer.innerHTML = html;
      
      // Update current view in state
      STATE.currentView = view;
      
      // Initialize the view
      initializeView(view, params);
      
      // Initialize lazy loading for any images in the view
      initLazyLoading(viewContainer);
      
    } catch (error) {
      console.error('Error loading view:', error);
      viewContainer.innerHTML = `
        <div class="alert alert-danger m-4" role="alert">
          <h4 class="alert-heading">Error Loading View</h4>
          <p>Sorry, we couldn't load the requested content. Please try again later.</p>
          <hr>
          <p class="mb-0">Error: ${error.message}</p>
        </div>
      `;
    }
  }

  /**
   * Initialize a view after it's loaded
   * @param {string} view - The view name to initialize
   * @param {object} params - Optional parameters for the view
   */
  function initializeView(view, params = {}) {
    // Common initialization
    setupSearchInView();
    
    // View-specific initialization
    switch (view) {
      case 'home':
        // Home view doesn't need special initialization
        break;
        
      case 'streaming':
        initStreamingView(params);
        break;
        
      case 'games':
        initGamesView(params);
        break;
        
      case 'game-detail':
        initGameDetailView(params);
        break;
        
      case 'kids':
        initKidsView(params);
        break;
        
      case 'favorites':
        initFavoritesView();
        break;
        
      case 'watchlater':
        initWatchLaterView();
        break;
    }
  }

  /**
   * Set up search functionality in the current view
   */
  function setupSearchInView() {
    const searchInput = document.querySelector(`#${STATE.currentView}Search`);
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim().toLowerCase();
        filterContentInView(query);
      });
    }
  }

  /**
   * Filter content in the current view based on search query
   * @param {string} query - The search query
   */
  function filterContentInView(query) {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
      const title = (card.querySelector('.card-title') || {}).textContent || '';
      const desc = (card.querySelector('.card-text') || {}).textContent || '';
      const searchText = `${title} ${desc}`.toLowerCase();
      
      if (query === '' || searchText.includes(query)) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }

  /**
   * Initialize the streaming view
   * @param {object} params - Optional parameters
   */
  async function initStreamingView(params = {}) {
    const contentContainer = document.getElementById('streaming-content');
    if (!contentContainer) return;
    
    try {
      // If we have a specific video ID, show the detail view
      if (params.videoId) {
        await loadStreamingDetail(params.videoId, contentContainer);
        } else {
        // Otherwise load the category listing
        await loadStreamingCategories(contentContainer);
      }
    } catch (error) {
      console.error('Error initializing streaming view:', error);
      contentContainer.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Failed to load streaming content: ${error.message}
        </div>
      `;
    }
  }

  /**
   * Load streaming categories and videos
   * @param {HTMLElement} container - The container element
   */
  async function loadStreamingCategories(container) {
    // Fetch streaming data
    const data = await fetchData('streaming');
    
    // Get the video card template
    const videoCardTemplate = document.getElementById('streaming-video-card-template');
    
    if (!videoCardTemplate) {
      throw new Error('Required templates not found');
    }
    
    // Clear the container
    container.innerHTML = '';
    
    // Number of videos to show initially per category
    const INITIAL_VIDEOS_TO_SHOW = 12;
    
    // Get the category bubbles container
    const categoryBubblesContainer = document.getElementById('category-bubbles');
    if (!categoryBubblesContainer) {
      throw new Error('Category bubbles container not found');
    }
    
    // Clear any existing category bubbles
    categoryBubblesContainer.innerHTML = '';
    
    // Create a container for category sections
    const categorySectionsContainer = document.createElement('div');
    categorySectionsContainer.className = 'category-sections';
    categorySectionsContainer.style.width = '100%';
    categorySectionsContainer.style.maxWidth = '1200px';
    categorySectionsContainer.style.overflowX = 'hidden';
    categorySectionsContainer.style.margin = '0 auto';
    container.appendChild(categorySectionsContainer);
    
    // Collect all categories
    const allCategories = [];
    
    // Process the data structure to get categories and their videos
    if (data.Content && Array.isArray(data.Content)) {
      data.Content.forEach(categoryGroup => {
        if (categoryGroup.Videos && Array.isArray(categoryGroup.Videos)) {
          // Each Videos array contains categories
          categoryGroup.Videos.forEach(category => {
            // Skip categories with no content
            if (!category.Content || !Array.isArray(category.Content) || category.Content.length === 0) {
              return;
            }
            
            allCategories.push(category);
          });
        }
      });
    }
    
    // If no categories found
    if (allCategories.length === 0) {
      container.innerHTML = `
        <div class="alert alert-info" role="alert">
          No streaming categories found.
        </div>
      `;
      return;
    }
    
    // Create category bubbles and sections
    allCategories.forEach((category, index) => {
      const isFirstCategory = index === 0;
      const categoryId = `category-${category.ID || Math.random().toString(36).substring(2, 9)}`;
      
      // Create category bubble
      const categoryBubble = document.createElement('div');
      categoryBubble.className = `category-bubble ${isFirstCategory ? 'active' : ''}`;
      categoryBubble.setAttribute('data-category-id', categoryId);
      
      if (category.Icon) {
        const categoryIcon = document.createElement('img');
        categoryIcon.src = category.Icon;
        categoryIcon.alt = '';
        categoryIcon.loading = 'lazy';
        categoryBubble.appendChild(categoryIcon);
      }
      
      const categoryName = document.createElement('span');
      categoryName.textContent = category.Name || 'Unnamed Category';
      categoryBubble.appendChild(categoryName);
      
      categoryBubblesContainer.appendChild(categoryBubble);
      
      // Create category section
      const categorySection = document.createElement('div');
      categorySection.className = `category-section ${isFirstCategory ? 'active' : ''}`;
      categorySection.id = categoryId;
      
      // Create video grid - use a container with max-width to force wrapping
      const videoGrid = document.createElement('div');
      videoGrid.className = 'category-videos';
      videoGrid.style.maxWidth = '100%';
      videoGrid.style.width = '100%';
      videoGrid.style.boxSizing = 'border-box';
      
      // Add videos to the grid
      const allVideos = category.Content || [];
      const initialVideos = allVideos.slice(0, INITIAL_VIDEOS_TO_SHOW);
      
      initialVideos.forEach(video => {
        const videoCard = createStreamingVideoCard(video, videoCardTemplate);
        if (videoCard) {
          videoGrid.appendChild(videoCard);
        }
      });
      
      categorySection.appendChild(videoGrid);
      
      // Add load more button if needed
      if (allVideos.length > INITIAL_VIDEOS_TO_SHOW) {
        const loadMoreContainer = document.createElement('div');
        loadMoreContainer.className = 'text-center mt-4';
        
        const loadMoreBtn = document.createElement('button');
        loadMoreBtn.className = 'btn btn-outline-primary load-more-btn';
        loadMoreBtn.textContent = `Load More (${allVideos.length - INITIAL_VIDEOS_TO_SHOW} more)`;
        
        let currentIndex = INITIAL_VIDEOS_TO_SHOW;
        
        loadMoreBtn.addEventListener('click', () => {
          const nextBatch = allVideos.slice(currentIndex, currentIndex + INITIAL_VIDEOS_TO_SHOW);
          const fragment = document.createDocumentFragment();
          
          nextBatch.forEach(video => {
            const videoCard = createStreamingVideoCard(video, videoCardTemplate);
            if (videoCard) {
              fragment.appendChild(videoCard);
            }
          });
          
          videoGrid.appendChild(fragment);
          currentIndex += nextBatch.length;
          
          // Initialize lazy loading for newly added images
          initLazyLoading(videoGrid);
          
          // Update or hide load more button
          if (currentIndex >= allVideos.length) {
            loadMoreContainer.style.display = 'none';
          } else {
            loadMoreBtn.textContent = `Load More (${allVideos.length - currentIndex} more)`;
          }
        });
        
        loadMoreContainer.appendChild(loadMoreBtn);
        categorySection.appendChild(loadMoreContainer);
      }
      
      // Add category section to container
      categorySectionsContainer.appendChild(categorySection);
    });
    
    // Set up category bubble click handlers
    const categoryBubbles = categoryBubblesContainer.querySelectorAll('.category-bubble');
    categoryBubbles.forEach(bubble => {
      bubble.addEventListener('click', () => {
        const categoryId = bubble.getAttribute('data-category-id');
        
        // Update active bubble
        categoryBubbles.forEach(b => b.classList.remove('active'));
        bubble.classList.add('active');
        
        // Show corresponding category section
        const categorySections = categorySectionsContainer.querySelectorAll('.category-section');
        categorySections.forEach(section => {
          section.classList.toggle('active', section.id === categoryId);
        });
        
        // Scroll the bubble into view if needed
        bubble.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        
        // Initialize lazy loading for the newly visible section
        const activeSection = document.getElementById(categoryId);
        if (activeSection) {
          initLazyLoading(activeSection);
        }
      });
    });
    
    // Set up navigation arrows
    const prevButton = document.querySelector('.category-nav-prev');
    const nextButton = document.querySelector('.category-nav-next');
    const scrollContainer = document.querySelector('.category-nav-scroll');
    
    if (prevButton && nextButton && scrollContainer) {
      prevButton.addEventListener('click', () => {
        scrollContainer.scrollBy({ left: -200, behavior: 'smooth' });
      });
      
      nextButton.addEventListener('click', () => {
        scrollContainer.scrollBy({ left: 200, behavior: 'smooth' });
      });
    }
    
    // Initialize lazy loading for the first visible section
    initLazyLoading(categorySectionsContainer.querySelector('.category-section.active'));
  }

  /**
   * Create a streaming video card from template
   * @param {object} video - The video data
   * @param {HTMLTemplateElement} template - The template element
   * @returns {HTMLElement} The created card element
   */
  function createStreamingVideoCard(video, template) {
    if (!video || !template) return null;
    
    const card = document.importNode(template.content, true);
    
    // Set video details
    const title = card.querySelector('.video-title');
    const image = card.querySelector('.card-img-top');
    const duration = card.querySelector('.duration-badge');
    const rating = card.querySelector('.video-rating');
    const viewsCount = card.querySelector('.views-count');
    const premiumBadge = card.querySelector('.premium-badge');
    const favoriteBtn = card.querySelector('.favorite-btn');
    const watchLaterBtn = card.querySelector('.watch-later-btn');
    
    // Set basic info
    if (title) title.textContent = video.Title || 'Untitled';
    if (image) {
      // Apply lazy loading to the image
      applyLazyLoading(
        image, 
        video.Thumbnail || video.Thumbnail_Large || PLACEHOLDER_IMAGE,
        video.Title || 'Video thumbnail'
      );
    }
    
    // Format duration from seconds to MM:SS or HH:MM:SS
    if (duration && video.Duration) {
      const durationSeconds = parseInt(video.Duration, 10);
      if (!isNaN(durationSeconds)) {
        const hours = Math.floor(durationSeconds / 3600);
        const minutes = Math.floor((durationSeconds % 3600) / 60);
        const seconds = durationSeconds % 60;
        
        if (hours > 0) {
          duration.textContent = `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
          duration.textContent = `${minutes}m`;
        } else {
          duration.textContent = `${seconds}s`;
        }
      } else {
        duration.textContent = video.Duration;
      }
    }
    
    if (rating) rating.textContent = video.avrate ? `${video.avrate}/5` : '';
    if (viewsCount) viewsCount.textContent = video.ViewCount ? `${video.ViewCount} views` : '';
    
    // Handle premium badge
    if (premiumBadge && video.isPremium === 'True') {
      premiumBadge.classList.remove('d-none');
    }
    
    // Make the card clickable to view details
    const cardElement = card.querySelector('.card');
    if (cardElement) {
      cardElement.style.cursor = 'pointer';
      cardElement.addEventListener('click', (e) => {
        // Prevent click if the target is a button
        if (e.target.closest('.action-icon')) return;
        
        navigateToView('streaming', { videoId: video.Id });
      });
    }
    
    // Set up favorite button
    if (favoriteBtn) {
      const isFavorite = STATE.favorites.some(fav => 
        fav.id === video.Id && fav.type === 'streaming'
      );
      
      if (isFavorite) {
        favoriteBtn.classList.add('active');
        favoriteBtn.querySelector('i').classList.replace('bi-heart', 'bi-heart-fill');
      }
      
      favoriteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleFavorite('streaming', video);
        
        const isActive = favoriteBtn.classList.toggle('active');
        favoriteBtn.querySelector('i').classList.toggle('bi-heart', !isActive);
        favoriteBtn.querySelector('i').classList.toggle('bi-heart-fill', isActive);
      });
    }
    
    // Set up watch later button
    if (watchLaterBtn) {
      const isWatchLater = STATE.watchLater.some(item => 
        item.id === video.Id && item.type === 'streaming'
      );
      
      if (isWatchLater) {
        watchLaterBtn.classList.add('active');
        watchLaterBtn.querySelector('i').classList.replace('bi-clock', 'bi-clock-fill');
      }
      
      watchLaterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleWatchLater('streaming', video);
        
        const isActive = watchLaterBtn.classList.toggle('active');
        watchLaterBtn.querySelector('i').classList.toggle('bi-clock', !isActive);
        watchLaterBtn.querySelector('i').classList.toggle('bi-clock-fill', isActive);
      });
    }
    
    return card;
  }

  /**
   * Load streaming detail view for a specific video
   * @param {string} videoId - The video ID
   * @param {HTMLElement} container - The container element
   */
  async function loadStreamingDetail(videoId, container) {
    // Implementation for streaming detail view
    // This would find the specific video by ID and display its details
    // For now, we'll just show a placeholder
    container.innerHTML = `
      <div class="alert alert-info" role="alert">
        Video detail view for ID: ${videoId} (To be implemented)
      </div>
    `;
  }

  /**
   * Initialize the games view
   * @param {object} params - Optional parameters
   */
  async function initGamesView(params = {}) {
   // console.log('App.js: initGamesView called');
    const contentContainer = document.getElementById('games-content');
    if (!contentContainer) {
      console.error('Games content container not found');
      return;
    }
    
    try {
      // If we have a specific game ID, show the detail view
      if (params.gameId) {
      //  console.log('Loading game detail for ID:', params.gameId);
        await loadGameDetail(params.gameId, contentContainer);
      } else {
       // console.log('Loading games categories from app.js');
        // Otherwise load the category listing
        await loadGameCategories(contentContainer);
      }
    } catch (error) {
      console.error('Error initializing games view:', error);
      contentContainer.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Failed to load games content: ${error.message}
        </div>
      `;
    }
  }

  /**
   * Load game categories and games
   * @param {HTMLElement} container - The container element
   */
  async function loadGameCategories(container) {
    try {
      // Use the external games.js implementation
      if (typeof window.loadGamesCategories === 'function') {
        await window.loadGamesCategories(container);
      } else {
        throw new Error('Games module not loaded properly');
      }
    } catch (error) {
      console.error('Error loading game categories:', error);
      container.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Failed to load games content: ${error.message}
        </div>
      `;
    }
  }

  /**
   * Load game detail view for a specific game
   * @param {string} gameId - The game ID
   * @param {HTMLElement} container - The container element
   */
  async function loadGameDetail(gameId, container) {
    try {
      // Load the game-detail.php view
      const response = await fetch(`${VIEWS_BASE}game-detail.php`);
      if (!response.ok) {
        throw new Error(`Failed to load game detail view: ${response.status}`);
      }
      
      const html = await response.text();
      container.innerHTML = html;
      
      // Initialize the game detail view
      if (typeof initGameDetailView === 'function') {
        initGameDetailView({ gameId });
      } else {
        console.error('initGameDetailView function not available');
      }
    } catch (error) {
      console.error('Error loading game detail view:', error);
      container.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Failed to load game detail view: ${error.message}
        </div>
      `;
    }
  }

  /**
   * Initialize the kids view
   * @param {object} params - Optional parameters
   */
  async function initKidsView(params = {}) {
    const contentContainer = document.getElementById('kids-content');
    if (!contentContainer) return;
    
    try {
      if (params.channelId && params.playlistId && params.videoId) {
        // Video detail view
        await loadKidsVideoDetail(params.channelId, params.playlistId, params.videoId, contentContainer);
      } else if (params.channelId && params.playlistId) {
        // Playlist detail view
        await loadKidsPlaylistDetail(params.channelId, params.playlistId, contentContainer);
      } else if (params.channelId) {
        // Channel detail view
        await loadKidsChannelDetail(params.channelId, contentContainer);
      } else {
        // Channel listing
        await loadKidsChannels(contentContainer);
      }
    } catch (error) {
      console.error('Error initializing kids view:', error);
      contentContainer.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Failed to load kids content: ${error.message}
        </div>
      `;
    }
  }

  /**
   * Load kids channels
   * @param {HTMLElement} container - The container element
   */
  async function loadKidsChannels(container) {
    // Implementation for kids channels listing
    container.innerHTML = `
      <div class="text-center py-5">
        <i class="bi bi-person-badge fs-1 text-muted mb-3"></i>
        <h3>Kids content loading...</h3>
        <p class="text-muted">This feature is coming soon</p>
      </div>
    `;
  }

  /**
   * Load kids channel detail
   * @param {string} channelId - The channel ID
   * @param {HTMLElement} container - The container element
   */
  async function loadKidsChannelDetail(channelId, container) {
    // Implementation for kids channel detail
    container.innerHTML = `
      <div class="alert alert-info" role="alert">
        Channel detail view for ID: ${channelId} (To be implemented)
      </div>
    `;
  }

  /**
   * Load kids playlist detail
   * @param {string} channelId - The channel ID
   * @param {string} playlistId - The playlist ID
   * @param {HTMLElement} container - The container element
   */
  async function loadKidsPlaylistDetail(channelId, playlistId, container) {
    // Implementation for kids playlist detail
    container.innerHTML = `
      <div class="alert alert-info" role="alert">
        Playlist detail view for Channel: ${channelId}, Playlist: ${playlistId} (To be implemented)
      </div>
    `;
  }

  /**
   * Load kids video detail
   * @param {string} channelId - The channel ID
   * @param {string} playlistId - The playlist ID
   * @param {string} videoId - The video ID
   * @param {HTMLElement} container - The container element
   */
  async function loadKidsVideoDetail(channelId, playlistId, videoId, container) {
    // Implementation for kids video detail
    container.innerHTML = `
      <div class="alert alert-info" role="alert">
        Video detail view for Channel: ${channelId}, Playlist: ${playlistId}, Video: ${videoId} (To be implemented)
      </div>
    `;
  }

  /**
   * Initialize the favorites view
   */
  function initFavoritesView() {
    const contentContainer = document.getElementById('favorites-content');
    const emptyContainer = document.getElementById('favorites-empty');
    
    if (!contentContainer || !emptyContainer) return;
    
    if (STATE.favorites.length === 0) {
      contentContainer.style.display = 'none';
      emptyContainer.style.display = 'block';
    } else {
      contentContainer.style.display = 'block';
      emptyContainer.style.display = 'none';
      
      // Render favorites
      renderFavorites(contentContainer);
    }
  }

  /**
   * Render favorites in the container
   * @param {HTMLElement} container - The container element
   */
  function renderFavorites(container) {
    // Implementation for rendering favorites
    container.innerHTML = `
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
        ${STATE.favorites.map(item => `
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="position-relative">
                <img src="${item.image || PLACEHOLDER_IMAGE}" class="card-img-top" alt="${item.title}" style="height: 180px; object-fit: cover;">
              </div>
              <div class="card-body">
                <h5 class="card-title">${item.title}</h5>
                <p class="card-text small text-muted">${item.description || ''}</p>
              </div>
              <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                <span class="badge bg-secondary">${item.type}</span>
                <button class="btn btn-sm btn-outline-danger remove-favorite" data-id="${item.id}" data-type="${item.type}">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;
    
    // Add event listeners to remove buttons
    const removeButtons = container.querySelectorAll('.remove-favorite');
    removeButtons.forEach(button => {
      button.addEventListener('click', () => {
        const id = button.getAttribute('data-id');
        const type = button.getAttribute('data-type');
        removeFavorite(id, type);
        initFavoritesView(); // Refresh the view
      });
    });
  }

  /**
   * Initialize the watch later view
   */
  function initWatchLaterView() {
    const contentContainer = document.getElementById('watchlater-content');
    const emptyContainer = document.getElementById('watchlater-empty');
    
    if (!contentContainer || !emptyContainer) return;
    
    if (STATE.watchLater.length === 0) {
      contentContainer.style.display = 'none';
      emptyContainer.style.display = 'block';
      } else {
      contentContainer.style.display = 'block';
      emptyContainer.style.display = 'none';
      
      // Render watch later items
      renderWatchLater(contentContainer);
    }
  }

  /**
   * Render watch later items in the container
   * @param {HTMLElement} container - The container element
   */
  function renderWatchLater(container) {
    // Implementation similar to renderFavorites
    container.innerHTML = `
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
        ${STATE.watchLater.map(item => `
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="position-relative">
                <img src="${item.image || PLACEHOLDER_IMAGE}" class="card-img-top" alt="${item.title}" style="height: 180px; object-fit: cover;">
              </div>
              <div class="card-body">
                <h5 class="card-title">${item.title}</h5>
                <p class="card-text small text-muted">${item.description || ''}</p>
              </div>
              <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                <span class="badge bg-secondary">${item.type}</span>
                <button class="btn btn-sm btn-outline-danger remove-watchlater" data-id="${item.id}" data-type="${item.type}">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;
    
    // Add event listeners to remove buttons
    const removeButtons = container.querySelectorAll('.remove-watchlater');
    removeButtons.forEach(button => {
      button.addEventListener('click', () => {
        const id = button.getAttribute('data-id');
        const type = button.getAttribute('data-type');
        removeWatchLater(id, type);
        initWatchLaterView(); // Refresh the view
    });
  });
  }

  /**
   * Toggle an item in favorites
   * @param {string} type - The item type (streaming, games, kids)
   * @param {object} item - The item data
   */
  function toggleFavorite(type, item) {
    const id = item.Id || item.id;
    const existingIndex = STATE.favorites.findIndex(fav => 
      fav.id === id && fav.type === type
    );
    
    if (existingIndex >= 0) {
      // Remove from favorites
      STATE.favorites.splice(existingIndex, 1);
    } else {
      // Add to favorites
      STATE.favorites.push({
        id,
        type,
        title: item.Title || item.title || 'Untitled',
        description: item.Description || item.description || '',
        image: item.Thumbnail || item.Thumbnail_Large || item.imageCropped || item.imageFile || PLACEHOLDER_IMAGE,
        url: item.Content || item.sourceFile || item.url || ''
      });
    }
    
    // Save to localStorage
    saveUserPreferences();
  }
  
  // Expose toggleFavorite to global scope for other modules
  window.toggleFavorite = toggleFavorite;

  /**
   * Remove an item from favorites
   * @param {string} id - The item ID
   * @param {string} type - The item type
   */
  function removeFavorite(id, type) {
    const existingIndex = STATE.favorites.findIndex(fav => 
      fav.id === id && fav.type === type
    );
    
    if (existingIndex >= 0) {
      STATE.favorites.splice(existingIndex, 1);
      saveUserPreferences();
    }
  }

  /**
   * Toggle an item in watch later
   * @param {string} type - The item type (streaming, games, kids)
   * @param {object} item - The item data
   */
  function toggleWatchLater(type, item) {
    const id = item.Id || item.id;
    const existingIndex = STATE.watchLater.findIndex(wl => 
      wl.id === id && wl.type === type
    );
    
    if (existingIndex >= 0) {
      // Remove from watch later
      STATE.watchLater.splice(existingIndex, 1);
    } else {
      // Add to watch later
      STATE.watchLater.push({
        id,
        type,
        title: item.Title || item.title || 'Untitled',
        description: item.Description || item.description || '',
        image: item.Thumbnail || item.Thumbnail_Large || item.imageCropped || item.imageFile || PLACEHOLDER_IMAGE,
        url: item.Content || item.sourceFile || item.url || ''
      });
    }
    
    // Save to localStorage
    saveUserPreferences();
  }

  /**
   * Remove an item from watch later
   * @param {string} id - The item ID
   * @param {string} type - The item type
   */
  function removeWatchLater(id, type) {
    const existingIndex = STATE.watchLater.findIndex(wl => 
      wl.id === id && wl.type === type
    );
    
    if (existingIndex >= 0) {
      STATE.watchLater.splice(existingIndex, 1);
      saveUserPreferences();
    }
  }

  /**
   * Apply lazy loading to an image element
   * @param {HTMLImageElement} img - The image element
   * @param {string} src - The image source URL
   * @param {string} alt - The image alt text
   */
  function applyLazyLoading(img, src, alt) {
    if (!img) return;
    
    img.alt = alt || '';
    
    if (lazyImageObserver) {
      // Use Intersection Observer for lazy loading
      img.setAttribute('data-src', src || PLACEHOLDER_IMAGE);
      img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E'; // Tiny placeholder
      img.classList.add('lazy-image');
      lazyImageObserver.observe(img);
    } else {
      // Fallback for browsers that don't support Intersection Observer
      img.src = src || PLACEHOLDER_IMAGE;
    }
    
    // Handle image load errors
    img.onerror = () => { 
      img.src = PLACEHOLDER_IMAGE;
      img.classList.add('img-error');
    };
  }

  /**
   * Initialize lazy loading for all images in the container
   * @param {HTMLElement} container - The container element
   */
  function initLazyLoading(container) {
    if (!container || !lazyImageObserver) return;
    
    const lazyImages = container.querySelectorAll('img.lazy-image');
    lazyImages.forEach(img => {
      lazyImageObserver.observe(img);
    });
  }

  /**
   * Fetch data from the API
   * @param {string} route - The API route (streaming, games, kids)
   * @returns {Promise<object>} The parsed JSON data
   */
  async function fetchData(route, lang) {
    // Get language from parameter or localStorage
    const currentLang = lang || document.documentElement.getAttribute('lang') || 'en';
    const url = `${API_BASE}${route}&lang=${currentLang}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`API error: ${response.status}`);
    }
    return response.json();
  }
  
  // Expose fetchData to global scope for other modules
  window.fetchData = fetchData;
  window.initLazyLoading = initLazyLoading;

  // Initialize the app when the DOM is loaded
  document.addEventListener('DOMContentLoaded', init);

})();