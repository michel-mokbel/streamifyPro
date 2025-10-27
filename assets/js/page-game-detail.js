(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Re-initialize when language changes

  async function init() {
    const params = new URLSearchParams(window.location.search);
    const targetId = params.get('id');
    const targetCategoryName = params.get('category');

    // Fallback: if no params, go back to games
    if (!targetId) {
      window.location.replace('games.php');
      return;
    }

    // Get current language
    const currentLang = document.documentElement.getAttribute('lang') || 'en';
    const data = await fetchData('games', currentLang);

    // Find the category and game
    let foundCategory = null;
    let foundGame = null;
    (data.Content || []).forEach(group => {
      (group.HTML5 || []).forEach(cat => {
        if (foundGame) return;
        if (targetCategoryName && cat.Name !== decodeURIComponent(targetCategoryName)) {
          // continue search — category hint provided
        }
        (cat.Content || []).forEach(g => {
          if (foundGame) return;
          if (String(g.ID) === String(targetId)) {
            foundCategory = cat;
            foundGame = g;
          }
        });
      });
    });

    if (!foundGame) {
      // Try a relaxed search by title
      const lower = (targetId || '').toLowerCase();
      (data.Content || []).forEach(group => {
        (group.HTML5 || []).forEach(cat => {
          (cat.Content || []).forEach(g => {
            if (foundGame) return;
            if ((g.Title || '').toLowerCase() === lower) { foundCategory = cat; foundGame = g; }
          });
        });
      });
    }

    if (!foundGame) {
      document.querySelector('.container').innerHTML = `<div class="alert alert-danger mt-4">Game not found.</div>`;
      return;
    }

    render(foundGame, foundCategory);
  }

  function render(game, category) {
    const title = document.getElementById('gd-title');
    const desc = document.getElementById('gd-desc');
    const poster = document.getElementById('gd-poster');
    const plays = document.getElementById('gd-plays');
    const views = document.getElementById('gd-views');
    const rating = document.getElementById('gd-rating');
    const premium = document.getElementById('gd-premium');
    const playBtn = document.getElementById('gd-play');

    // Get current language and use Arabic translations if available
    const currentLang = document.documentElement.getAttribute('lang') || 'en';
    const gameTitle = currentLang === 'ar' && game.title_ar ? game.title_ar : game.Title;
    const gameDesc = currentLang === 'ar' && game.description_ar ? game.description_ar : game.Description;
    
    // Debug logging
    // console.log('Game Detail - Language:', currentLang);
    // console.log('Game Detail - Has title_ar?', !!game.title_ar);
    // console.log('Game Detail - English Title:', game.Title);
    // console.log('Game Detail - Arabic Title:', game.title_ar);
    // console.log('Game Detail - Selected Title:', gameTitle);
    
    title.textContent = gameTitle || 'Untitled';
    desc.textContent = gameDesc || '';
    applyLazyLoading(poster, game.Thumbnail_Large || game.Thumbnail || window.PLACEHOLDER_IMAGE, game.Title || 'Game');
    const metrics = getSyntheticGameMetrics(game);
    plays.textContent = formatNumber(metrics.plays);
    views.textContent = formatNumber(metrics.views);
    const notRatedText = window.i18n?.t('games.rating') ? `${window.i18n.t('games.rating')}: 0/5` : 'Not rated';
    rating.textContent = metrics.rating ? `${metrics.rating.toFixed(1)}/5` : notRatedText;
    if (game.isPremium === 'True') premium.classList.remove('d-none');

    // Play
    playBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (!game.Content) return;
      window.open(game.Content, '_blank');
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
        
        const isFav = Array.isArray(favList) && favList.some(x => String(x.id) === String(game.ID) && x.type === 'game');
        const isWl = Array.isArray(wlList) && wlList.some(x => String(x.id) === String(game.ID) && x.type === 'game');
        
        setFavUI(isFav);
        setWlUI(isWl);
      }
      
      // Favorite button click
      favBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof window.toggleFavorite === 'function') {
          window.toggleFavorite('game', {
            Id: game.ID,
            Title: game.Title,
            Thumbnail: game.Thumbnail || game.Thumbnail_Large || window.PLACEHOLDER_IMAGE,
            Content: game.Content || ''
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
          window.toggleWatchLater('game', {
            Id: game.ID,
            Title: game.Title,
            Thumbnail: game.Thumbnail || game.Thumbnail_Large || window.PLACEHOLDER_IMAGE,
            Content: game.Content || ''
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

    // Sidebar: collection
    const categoryName = currentLang === 'ar' && category?.name_ar ? category.name_ar : category?.Name;
    document.getElementById('gd-cat-name').textContent = categoryName || (window.i18n?.t('gameDetail.category') || 'Collection');
    document.getElementById('gd-related-title').textContent = window.i18n?.t('gameDetail.similarGames') || `More from ${categoryName || 'this collection'}`;
    const img = document.getElementById('gd-cat-img');
    if (category?.Icon) { img.src = category.Icon; img.alt = categoryName; } else { img.src = window.PLACEHOLDER_IMAGE; }
    const count = (category?.Content || []).length; 
    const gamesText = count === 1 ? (window.i18n?.t('favorites.game') || 'game') : (window.i18n?.t('sidebar.games') || 'games');
    document.getElementById('gd-cat-count').textContent = `${count} ${gamesText}`;

    // Tags (optional - only if element exists)
    const tags = document.getElementById('gd-tags');
    if (tags) {
      tags.innerHTML = '';
      (game.Category || []).forEach(t => {
        const b = document.createElement('span');
        b.className = 'badge bg-light text-dark rounded-pill me-1 mb-1';
        b.textContent = t; tags.appendChild(b);
      });
    }

    // Related
    const related = document.getElementById('gd-related');
    related.innerHTML = '';
    const playsText = window.i18n?.t('games.plays') || 'plays';
    (category?.Content || []).filter(g => g.ID !== game.ID).slice(0, 5).forEach(rg => {
      const a = document.createElement('a'); 
      a.href = `game-detail.php?id=${encodeURIComponent(rg.ID)}&category=${encodeURIComponent(category.Name)}`; 
      a.className = 'text-decoration-none related-game-item';
      
      // Get synthetic metrics for this related game
      const rgMetrics = getSyntheticGameMetrics(rg);
      
      // Use Arabic title if available
      const relatedGameTitle = currentLang === 'ar' && rg.title_ar ? rg.title_ar : rg.Title;
      
      a.innerHTML = `<div class=\"d-flex mb-3\"><div class=\"flex-shrink-0\" style=\"width: 100px;\"><img src=\"${rg.Thumbnail || rg.Thumbnail_Large || window.PLACEHOLDER_IMAGE}\" alt=\"\" class=\"rounded\" style=\"width: 100px; height: 56px; object-fit: cover;\"></div><div class=\"flex-grow-1 ms-3\"><div class=\"d-flex flex-column justify-content-between h-100\"><h6 class=\"mb-1\">${relatedGameTitle || 'Game'}</h6><p class=\"mb-0 text-muted small\">${formatNumber(rgMetrics.plays)} ${playsText}</p></div></div></div>`;
      related.appendChild(a);
    });
  }

  // Toast notification function
  function showToast(message) {
    // Remove any existing toast
    const existingToast = document.querySelector('.game-detail-toast');
    if (existingToast) {
      existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'game-detail-toast';
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

// Synthetic metrics helpers (match games pages)
function getSyntheticGameMetrics(game) {
  const key = String(game.ID || game.Id || game.Title || Math.random());
  const plays = clampInt(randFromKey(key + ':plays'), 2500, 980000);
  const views = plays + clampInt(randFromKey(key + ':vdelta'), 0, Math.round(plays * 0.6));
  const ratingRaw = 3.6 + (randFromKey(key + ':rating') / 4294967295) * (4.9 - 3.6);
  const rating = Math.round(ratingRaw * 10) / 10;
  return { plays, views, rating };
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


