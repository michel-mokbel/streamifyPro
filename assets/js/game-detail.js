/**
 * Game Detail View Handler
 * Handles displaying the details of a selected game
 */

// Check if we have access to required global functions and variables
if (typeof PLACEHOLDER_IMAGE === 'undefined') {
  console.warn('Warning: PLACEHOLDER_IMAGE is not available, using fallback');
  window.PLACEHOLDER_IMAGE = './assets/img/logo.png';
}

if (typeof STATE === 'undefined') {
  console.warn('Warning: STATE is not available, creating local state');
  window.STATE = {
    favorites: [],
    watchLater: [],
    viewData: {}
  };
}

/**
 * Initialize the game detail view
 */
function initGameDetailView(params = {}) {
  try {
    // console.log('Initializing game detail view', params);
    
    // Get game data from state
    const gameData = STATE.viewData.gameDetail;
    if (!gameData) {
      console.error('No game data found in state');
      navigateToView('games');
      return;
    }
    
    const { game, category } = gameData;
    // console.log('Game data:', game);
    // console.log('Category data:', category);
    
    // Hide loading indicator and show content
    const loadingIndicator = document.getElementById('game-detail-loading');
    const contentContainer = document.getElementById('game-detail-content');
    
    if (loadingIndicator) {
      loadingIndicator.style.display = 'none';
    }
    
    if (!contentContainer) {
      console.error('Game detail content container not found');
      return;
    }
    
    // Set game data
    const title = contentContainer.querySelector('.game-title');
    const description = contentContainer.querySelector('.game-description');
    const poster = contentContainer.querySelector('.game-poster');
    const playCount = contentContainer.querySelector('.game-playcount');
    const viewCount = contentContainer.querySelector('.game-views');
    const rating = contentContainer.querySelector('.game-rating');
    const premiumBadge = contentContainer.querySelector('.premium-badge');
    const playButton = contentContainer.querySelector('.play-game-btn');
    const favoriteBtn = contentContainer.querySelector('.favorite-btn');
    
    // Set title
    title.textContent = game.Title || 'Untitled Game';
    
    // Set description
    description.textContent = game.Description || 'No description available';
    
    // Set image
    const imageUrl = game.Thumbnail_Large || game.Thumbnail || PLACEHOLDER_IMAGE;
    poster.src = imageUrl;
    poster.alt = game.Title;
    
    // Use synthetic metrics for consistent numbers
    const metrics = getSyntheticGameMetrics(game);
    
    // Set play count
    playCount.textContent = formatNumber(metrics.plays);
    
    // Set view count
    viewCount.textContent = formatNumber(metrics.views);
    
    // Set rating
    const notRatedText = window.i18n?.t('games.rating') ? `${window.i18n.t('games.rating')}: 0/5` : 'Not rated';
    rating.textContent = metrics.rating ? `${metrics.rating.toFixed(1)}/5` : notRatedText;
    
    // Set premium badge
    if (game.isPremium === 'True') {
      premiumBadge.classList.remove('d-none');
    }
    
    // Set play button
    if (game.Content) {
      playButton.addEventListener('click', (e) => {
        e.preventDefault();
        openGameModal(game, category);
      });
    } else {
      playButton.classList.add('disabled');
    }
    
    // Set category information
    const categoryName = contentContainer.querySelector('.category-name');
    const categoryImage = contentContainer.querySelector('.category-image');
    const categoryCount = contentContainer.querySelector('.category-count');
    
    categoryName.textContent = category.Name || 'Unknown Collection';
    
    if (category.Icon) {
      categoryImage.src = category.Icon;
      categoryImage.alt = category.Name;
    } else {
      categoryImage.src = PLACEHOLDER_IMAGE;
      categoryImage.alt = 'Category';
    }
    
    const gamesCount = category.Content ? category.Content.length : 0;
    const gamesText = gamesCount === 1 ? (window.i18n?.t('favorites.game') || 'game') : (window.i18n?.t('sidebar.games') || 'games');
    categoryCount.textContent = `${gamesCount} ${gamesText}`;
    
    // Set game categories
    const categoriesContainer = contentContainer.querySelector('.game-categories');
    categoriesContainer.innerHTML = ''; // Clear loading placeholder
    
    if (game.Category && game.Category.length) {
      game.Category.forEach(cat => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-dark me-1 mb-1';
        badge.textContent = cat;
        categoriesContainer.appendChild(badge);
      });
    } else {
      const noCat = document.createElement('span');
      noCat.className = 'text-muted small';
      noCat.textContent = 'No categories';
      categoriesContainer.appendChild(noCat);
    }
    
    // Set related games
    const relatedGamesContainer = contentContainer.querySelector('.related-games');
    const relatedGamesTitle = contentContainer.querySelector('.related-games-title');
    relatedGamesTitle.textContent = window.i18n?.t('gameDetail.similarGames') || `More from ${category.Name}`;
    
    // Clear loading placeholders
    relatedGamesContainer.innerHTML = '';
    
    // Get related games (excluding current game)
    const relatedGames = category.Content.filter(g => g.ID !== game.ID).slice(0, 5);
    
    if (relatedGames.length) {
      relatedGames.forEach(relatedGame => {
        const relatedTemplate = document.getElementById('related-game-item-template');
        if (!relatedTemplate) return;
        
        const relatedItem = relatedTemplate.content.cloneNode(true);
        
        const link = relatedItem.querySelector('.related-game-item');
        const img = relatedItem.querySelector('img');
        const title = relatedItem.querySelector('.related-game-title');
        const playsText = relatedItem.querySelector('.related-game-plays');
        
        title.textContent = relatedGame.Title || 'Untitled Game';
        
        const imgUrl = relatedGame.Thumbnail || relatedGame.Thumbnail_Large || PLACEHOLDER_IMAGE;
        img.src = imgUrl;
        img.alt = relatedGame.Title;
        
        // Set play count using synthetic metrics
        const metrics = getSyntheticGameMetrics(relatedGame);
        const plays = metrics.plays;
        const playText = plays === 1 ? (window.i18n?.t('games.play') || 'play') : (window.i18n?.t('games.plays') || 'plays');
        playsText.textContent = `${formatNumber(plays)} ${playText}`;
        
        
        // Add click handler
        link.addEventListener('click', (e) => {
          e.preventDefault();
          navigateToGameDetail(relatedGame, category);
        });
        
        relatedGamesContainer.appendChild(relatedItem);
      });
    } else {
      const noRelated = document.createElement('p');
      noRelated.className = 'text-muted small';
      noRelated.textContent = window.i18n?.t('games.noGamesAvailable') || 'No related games found';
      relatedGamesContainer.appendChild(noRelated);
    }
    
    // Remove screenshots section as it's not in the new design
    
  } catch (error) {
    console.error('Error initializing game detail view:', error);
    navigateToView('games');
  }
}

/**
 * Open game in a modal
 */
function openGameModal(game, category, isScreenshot = false) {
  // Use the existing modal in the DOM
  let modal = document.getElementById('gameModal');
  if (!modal) {
    console.error('Game modal not found in the DOM');
    return;
  }
  
  // Set modal content
  const modalTitle = modal.querySelector('.modal-title');
  const modalBody = modal.querySelector('.modal-body');
  
  if (isScreenshot) {
    // Screenshot mode - show image instead of iframe
    modalTitle.textContent = `${game.Title || 'Game'} - Screenshot`;
    
    // Clear previous content
    modalBody.innerHTML = '';
    
    // Create image element
    const img = document.createElement('img');
    img.src = game.Thumbnail_Large || game.Thumbnail || PLACEHOLDER_IMAGE;
    img.alt = `${game.Title} screenshot`;
    img.className = 'img-fluid w-100';
    
    modalBody.appendChild(img);
  } else {
    // Game mode - show iframe
    modalTitle.textContent = game.Title || 'Game';
    
    // Clear previous content
    modalBody.innerHTML = '';
    
    // Create ratio container and iframe
    const ratioDiv = document.createElement('div');
    ratioDiv.className = 'ratio ratio-16x9';
    
    const iframe = document.createElement('iframe');
    iframe.src = game.Content || '';
    iframe.setAttribute('allowfullscreen', '');
    
    ratioDiv.appendChild(iframe);
    modalBody.appendChild(ratioDiv);
    
    // Add event listener to reset iframe when modal is closed
    modal.addEventListener('hidden.bs.modal', () => {
      iframe.src = '';
    }, { once: true });
  }
  
  // Initialize Bootstrap modal
  const modalInstance = new bootstrap.Modal(modal);
  modalInstance.show();
}

/**
 * Navigate to game detail view
 */
function navigateToGameDetail(game, category) {
  // Save game and category to state
  STATE.viewData.gameDetail = {
    game,
    category
  };
  
  // Navigate to detail view
  if (typeof navigateToView === 'function') {
    navigateToView('game-detail');
  } else {
    console.error('navigateToView function not available');
    window.location.hash = 'game-detail';
  }
}

/**
 * Format number for display (e.g., 1000 -> 1K)
 */
function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  }
  return num.toString();
}

/**
 * Generate synthetic metrics for a game (consistent per game)
 */
function getSyntheticGameMetrics(game) {
  const key = String(game.ID || game.Id || game.Title || Math.random());
  const plays = clampInt(randFromKey(key + ':plays'), 2500, 980000);
  const views = plays + clampInt(randFromKey(key + ':vdelta'), 0, Math.round(plays * 0.6));
  const ratingRaw = 3.6 + (randFromKey(key + ':rating') / 4294967295) * (4.9 - 3.6);
  const rating = Math.round(ratingRaw * 10) / 10;
  return { plays, views, rating };
}

/**
 * Simple hash function for consistent pseudo-random numbers
 */
function randFromKey(key) {
  let h = 2166136261 >>> 0;
  for (let i = 0; i < key.length; i++) {
    h ^= key.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

/**
 * Clamp a value between min and max
 */
function clampInt(seed, min, max) {
  if (max <= min) return min;
  const span = max - min;
  return min + (seed % (span + 1));
}

// Export functions for use in main app.js
window.initGameDetailView = initGameDetailView;
window.openGameModal = openGameModal;