/**
 * Games View Handler
 * Handles all functionality for the Games section
 */

// Constants
const INITIAL_GAMES_TO_SHOW = 12;

// Check if we have access to required global functions and variables
if (typeof fetchData === 'undefined') {
  console.error('Error: fetchData function is not available. Make sure app.js is loaded before games.js');
}

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
 * Initialize the games view
 */
async function initGamesView(params = {}) {
  // console.log('Games.js: initGamesView called');
  try {
    const container = document.getElementById('games-content');
    if (!container) {
      console.error('Games container not found in games.js');
      return;
    }
    
    // console.log('Games container found, loading categories...');
    await loadGamesCategories(container);
  } catch (error) {
    console.error('Error initializing games view:', error);
  }
}

/**
 * Load games categories and display them
 */
async function loadGamesCategories(container) {
  try {
    // console.log('Loading games categories...');
    
    // Add a local implementation of initLazyLoading if the global one is not available
    const localInitLazyLoading = function(container) {
      // console.log('Using local initLazyLoading fallback');
      if (!container) return;
      
      const lazyImages = container.querySelectorAll('img.lazy-image');
      lazyImages.forEach(img => {
        const src = img.getAttribute('data-src');
        if (src) {
          // Just load the image directly
          img.src = src;
          img.removeAttribute('data-src');
        }
      });
    };
    
    // Use the global initLazyLoading if available, otherwise use local implementation
    window.initLazyLoading = window.initLazyLoading || localInitLazyLoading;
    
    // Fetch games data
    const data = await fetchData('games');
    // console.log('Games data received:', data);
    
    // Store data globally for debugging
    window.lastGameData = data;
    
    if (!data || !data.Content || !data.Content.length) {
      console.error('No valid games data found:', data);
      container.innerHTML = '<div class="alert alert-info">No games found</div>';
      return;
    }
    
    // Clear the container
    container.innerHTML = '';
    
    // Create a container for the category navigation
    const categoryNav = document.querySelector('.category-nav-container');
    const categoryBubblesContainer = document.getElementById('games-category-bubbles');
    
    // console.log('Category bubbles container:', categoryBubblesContainer);
    
    if (!categoryBubblesContainer) {
      console.error('Category bubbles container not found!');
      return;
    }
    
    // Create a container for category sections
    const categorySectionsContainer = document.createElement('div');
    categorySectionsContainer.className = 'category-sections';
    categorySectionsContainer.style.width = '100%';
    categorySectionsContainer.style.maxWidth = '1200px';
    categorySectionsContainer.style.overflowX = 'hidden';
    categorySectionsContainer.style.margin = '0 auto';
    container.appendChild(categorySectionsContainer);
    
    // Process each game category
    let isFirstCategory = true;
    let totalGames = 0;
    
    // console.log('Processing game categories...');
    
    data.Content.forEach((categoryGroup, index) => {
      // console.log(`Category group ${index}:`, categoryGroup);
      if (!categoryGroup.HTML5 || !categoryGroup.HTML5.length) {
        console.warn('No HTML5 games in this category group');
        return;
      }
      
      categoryGroup.HTML5.forEach((category, catIndex) => {
        // console.log(`Category ${catIndex} - ${category.Name}:`, category);
        if (!category.Content || !category.Content.length) {
          console.warn(`No games in category ${category.Name}`);
          return;
        }
        
        // Create a unique ID for this category
        const categoryId = `game-category-${category.Name.toLowerCase().replace(/\s+/g, '-')}`;
        
        // Create category bubble
        const categoryBubble = document.createElement('div');
        categoryBubble.className = `category-bubble ${isFirstCategory ? 'active' : ''}`;
        categoryBubble.setAttribute('data-category-id', categoryId);
        
        // Add category icon if available
        if (category.Icon) {
          const categoryIcon = document.createElement('img');
          categoryIcon.src = category.Icon;
          categoryIcon.alt = category.Name;
          categoryBubble.appendChild(categoryIcon);
        }
        
        // Add category name
        const categoryName = document.createElement('span');
        categoryName.textContent = category.Name;
        categoryBubble.appendChild(categoryName);
        
        categoryBubblesContainer.appendChild(categoryBubble);
        
        // Create a section for this category's games
        const categorySection = document.createElement('div');
        categorySection.className = `category-section ${isFirstCategory ? 'active' : ''}`;
        categorySection.id = categoryId;
        
        // Create a container for the games
        const gamesContainer = document.createElement('div');
        gamesContainer.className = 'category-games';
        categorySection.appendChild(gamesContainer);
        
        // Add games to the container
        const games = category.Content;
        totalGames += games.length;
        
        // Initially show a limited number of games
        const initialGames = games.slice(0, INITIAL_GAMES_TO_SHOW);
        initialGames.forEach(game => {
          const gameCard = createGameCard(game, category);
          gamesContainer.appendChild(gameCard);
        });
        
        // Add "Load More" button if there are more games
        if (games.length > INITIAL_GAMES_TO_SHOW) {
          const loadMoreContainer = document.createElement('div');
          loadMoreContainer.className = 'text-center w-100 mt-3 mb-4';
          
          const loadMoreBtn = document.createElement('button');
          loadMoreBtn.className = 'btn btn-outline-primary';
          loadMoreBtn.textContent = `Load More (${games.length - INITIAL_GAMES_TO_SHOW} more)`;
          
          loadMoreBtn.addEventListener('click', () => {
            // Get currently shown games
            const shownGames = gamesContainer.querySelectorAll('.game-card-wrapper').length;
            
            // Get next batch of games
            const nextBatch = games.slice(shownGames, shownGames + INITIAL_GAMES_TO_SHOW);
            
            // Add next batch to container
            nextBatch.forEach(game => {
              const gameCard = createGameCard(game, category);
              gamesContainer.appendChild(gameCard);
            });
            
            // Initialize lazy loading for new cards
            initLazyLoading(gamesContainer);
            
            // Update or remove the "Load More" button
            const remaining = games.length - (shownGames + nextBatch.length);
            if (remaining > 0) {
              loadMoreBtn.textContent = `Load More (${remaining} more)`;
            } else {
              loadMoreContainer.remove();
            }
          });
          
          loadMoreContainer.appendChild(loadMoreBtn);
          categorySection.appendChild(loadMoreContainer);
        }
        
        categorySectionsContainer.appendChild(categorySection);
        
        // Set up for next iteration
        if (isFirstCategory) {
          isFirstCategory = false;
        }
      });
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
    
    // Initialize lazy loading for the first visible category
    const firstCategory = document.querySelector('.category-section.active');
    if (firstCategory) {
      initLazyLoading(firstCategory);
    }
    
  } catch (error) {
    console.error('Error loading games categories:', error);
    
    // Try to extract more details from the error
    let errorDetails = '';
    if (error.message) {
      errorDetails = `: ${error.message}`;
    }
    
    // Check if we have data in the console but failed to render it
    let data;
    try {
      data = window.lastGameData;
    } catch (e) {
      // Ignore
    }
    
    container.innerHTML = `
      <div class="alert alert-danger">
        Failed to load games${errorDetails}. Please try again later.
        <button class="btn btn-sm btn-outline-danger mt-2" onclick="window.location.reload()">Reload Page</button>
      </div>
    `;
  }
}

/**
 * Create a game card element
 */
function createGameCard(game, category) {
  // Get the template
  const template = document.getElementById('game-card-template');
  if (!template) return document.createElement('div');
  
  // Clone the template
  const clone = template.content.cloneNode(true);
  
  // Set game data
  const card = clone.querySelector('.game-card');
  const title = clone.querySelector('.game-title');
  const description = clone.querySelector('.game-description');
  const playCount = clone.querySelector('.game-playcount');
  const premiumBadge = clone.querySelector('.premium-badge');
  const img = clone.querySelector('.card-img-top');
  
  // Set title
  title.textContent = game.Title || 'Untitled Game';
  
  // Set description (truncated)
  description.textContent = game.Description || 'No description available';
  description.classList.add('text-truncate-2');
  
  // Set play count
  const synthetic = getSyntheticGameMetrics(game);
  playCount.textContent = synthetic.plays > 0 ? formatNumber(synthetic.plays) : 'New';
  
  // Set premium badge
  if (game.isPremium === 'True') {
    premiumBadge.classList.remove('d-none');
  }
  
  // Set image with lazy loading
  const imageUrl = game.Thumbnail_Large || game.Thumbnail || PLACEHOLDER_IMAGE;
  img.setAttribute('data-src', imageUrl);
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
  img.alt = game.Title || 'Game thumbnail';
  img.classList.add('lazy-image');
  
  // Fallback if lazy loading fails
  setTimeout(() => {
    if (!img.src || img.src === 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E') {
      img.src = imageUrl;
    }
  }, 500);
  
  // Set up image error handling
  img.onerror = function() {
    this.src = PLACEHOLDER_IMAGE;
    this.classList.add('img-error');
  };
  
  // Add event listener for the play button
  const playBtn = clone.querySelector('.play-btn');
  playBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (game && game.Content) {
      window.open(game.Content, '_blank');
    }
  });
  
  // Add event listener for the favorite button
  const favoriteBtn = clone.querySelector('.favorite-btn');
  
  // Check if the game is in favorites
  const isFavorite = STATE.favorites && STATE.favorites.some(fav => 
    fav.id === game.ID && fav.type === 'games'
  );
  
  // Update button appearance
  if (isFavorite) {
    favoriteBtn.classList.add('active');
    favoriteBtn.querySelector('i').classList.remove('bi-heart');
    favoriteBtn.querySelector('i').classList.add('bi-heart-fill');
  }
  
  favoriteBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (typeof toggleFavorite === 'function') {
      toggleFavorite('games', game);
      
      // Toggle button appearance
      favoriteBtn.classList.toggle('active');
      favoriteBtn.querySelector('i').classList.toggle('bi-heart');
      favoriteBtn.querySelector('i').classList.toggle('bi-heart-fill');
    } else {
      console.error('toggleFavorite function not available');
    }
  });
  
  // Make the entire card clickable
  card.addEventListener('click', (e) => {
    // Ignore clicks on buttons
    if (e.target.closest('.action-icon')) return;
    
    // Navigate to game detail view
    navigateToGameDetail(game, category);
  });
  
  return clone;
}

// Deterministic synthetic metrics for games
function getSyntheticGameMetrics(game) {
  const key = String(game.ID || game.Id || game.Title || Math.random());
  const plays = clampInt(randFromKey(key + ':plays'), 2500, 980000);
  const views = plays + clampInt(randFromKey(key + ':vdelta'), 0, Math.round(plays * 0.6));
  const ratingRaw = 3.6 + (randFromKey(key + ':rating') / 4294967295) * (4.9 - 3.6);
  const rating = Math.round(ratingRaw * 10) / 10;
  return { plays, views, rating };
}

function randFromKey(key) {
  let h = 2166136261 >>> 0; // FNV-1a 32-bit
  for (let i = 0; i < key.length; i++) {
    h ^= key.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

function clampInt(seed, min, max) {
  if (max <= min) return min;
  const span = max - min;
  return min + (seed % (span + 1));
}

/**
 * Open game in a modal
 */
function openGameModal(game, category) {
  // Create modal element if it doesn't exist
  let modal = document.getElementById('gameModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'gameModal';
    modal.className = 'modal fade';
    modal.tabIndex = '-1';
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div class="ratio ratio-16x9">
              <iframe src="" allowfullscreen></iframe>
            </div>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
  }
  
  // Set modal content
  const modalTitle = modal.querySelector('.modal-title');
  const iframe = modal.querySelector('iframe');
  
  modalTitle.textContent = game.Title || 'Game';
  iframe.src = game.Content || '';
  
  // Initialize Bootstrap modal
  const modalInstance = new bootstrap.Modal(modal);
  modalInstance.show();
  
  // Add event listener to reset iframe when modal is closed
  modal.addEventListener('hidden.bs.modal', () => {
    iframe.src = '';
  });
}

/**
 * Navigate to game detail view
 */
function navigateToGameDetail(game, category) {
  // console.log('Navigating to game detail for:', game.Title);
  
  // Save game and category to state
  STATE.viewData.gameDetail = {
    game,
    category
  };
  
  // Force a small delay to ensure state is updated before navigation
  setTimeout(() => {
    // Navigate to detail view
    if (typeof navigateToView === 'function') {
      navigateToView('game-detail');
    } else {
      console.error('navigateToView function not available');
      window.location.hash = 'game-detail';
    }
  }, 10);
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

// Export functions for use in main app.js
window.initGamesView = initGamesView;
window.loadGamesCategories = loadGamesCategories;
