/** Page: Games (multi-page version) */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    const container = document.getElementById('games-content');
    if (!container) return;

    try {
      // Get current language from HTML tag (set by PHP)
      const currentLang = document.documentElement.getAttribute('lang') || 'en';
      const data = await fetchData('games', currentLang);

      const bubbles = document.getElementById('games-category-bubbles');
      const sectionsWrapper = document.createElement('div');
      sectionsWrapper.className = 'category-sections';
      sectionsWrapper.style.maxWidth = '1200px';
      sectionsWrapper.style.margin = '0 auto';
      container.innerHTML = '';
      container.appendChild(sectionsWrapper);

      const INITIAL_GAMES_TO_SHOW = 12;
      let first = true;

      data.Content.forEach(group => {
        (group.HTML5 || []).forEach(category => {
          if (!category.Content || category.Content.length === 0) return;

          const categoryId = `game-category-${category.Name.toLowerCase().replace(/\s+/g, '-')}`;
          
          // Get category name (Arabic if available)
          let categoryName = category.Name;
          if (currentLang === 'ar') {
            // Try categoryNameAr from JSON first
            if (category.categoryNameAr) {
              categoryName = category.categoryNameAr;
            } else if (window.i18n && typeof window.i18n.t === 'function') {
              // Fall back to translation system
              const translatedName = window.i18n.t(`gameCategories.${category.Name}`);
              if (translatedName && translatedName !== `gameCategories.${category.Name}`) {
                categoryName = translatedName;
              }
            }
          }

          // Bubble
          const bubble = document.createElement('div');
          bubble.className = `category-bubble ${first ? 'active' : ''}`;
          bubble.setAttribute('data-category-id', categoryId);
          if (category.Icon) {
            const ic = document.createElement('img');
            ic.src = category.Icon; ic.alt = categoryName; bubble.appendChild(ic);
          }
          const label = document.createElement('span');
          label.textContent = categoryName; bubble.appendChild(label);
          bubbles.appendChild(bubble);

          // Section
          const section = document.createElement('div');
          section.className = `category-section ${first ? 'active' : ''}`;
          section.id = categoryId;

          const grid = document.createElement('div');
          grid.className = 'category-games';
          section.appendChild(grid);

          category.Content.slice(0, INITIAL_GAMES_TO_SHOW).forEach(game => {
            grid.appendChild(createGameCard(game, category));
          });

          // Load more
          if (category.Content.length > INITIAL_GAMES_TO_SHOW) {
            const loadWrap = document.createElement('div');
            loadWrap.className = 'text-center w-100 mt-3 mb-4';
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-primary';
            const loadMoreText = window.i18n?.t('games.loadMore') || 'Load More';
            const moreText = window.i18n?.t('games.more') || 'more';
            btn.textContent = `${loadMoreText} (${category.Content.length - INITIAL_GAMES_TO_SHOW} ${moreText})`;
            let index = INITIAL_GAMES_TO_SHOW;
            btn.addEventListener('click', () => {
              const next = category.Content.slice(index, index + INITIAL_GAMES_TO_SHOW);
              next.forEach(game => grid.appendChild(createGameCard(game, category)));
              index += next.length;
              if (index >= category.Content.length) loadWrap.remove();
              else btn.textContent = `${loadMoreText} (${category.Content.length - index} ${moreText})`;
            });
            loadWrap.appendChild(btn);
            section.appendChild(loadWrap);
          }

          sectionsWrapper.appendChild(section);
          first = false;
        });
      });

      // Bubble interactions
      const allBubbles = bubbles.querySelectorAll('.category-bubble');
      allBubbles.forEach(b => b.addEventListener('click', () => {
        const id = b.getAttribute('data-category-id');
        allBubbles.forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        document.querySelectorAll('.category-section').forEach(s => s.classList.toggle('active', s.id === id));
      }));

      // Category bar prev/next buttons
      const navContainer = document.querySelector('.category-nav-container');
      const scroller = navContainer ? navContainer.querySelector('.category-nav-scroll') : null;
      const btnPrev = navContainer ? navContainer.querySelector('.category-nav-prev') : null;
      const btnNext = navContainer ? navContainer.querySelector('.category-nav-next') : null;
      if (scroller && btnPrev && btnNext) {
        const isRTL = (document.documentElement.getAttribute('dir') || 'ltr').toLowerCase() === 'rtl';
        const getStep = () => Math.max(200, Math.floor(scroller.clientWidth * 0.9));
        btnPrev.addEventListener('click', () => {
          const dx = getStep();
          scroller.scrollBy({ left: isRTL ? dx : -dx, behavior: 'smooth' });
        });
        btnNext.addEventListener('click', () => {
          const dx = getStep();
          scroller.scrollBy({ left: isRTL ? -dx : dx, behavior: 'smooth' });
        });
      }
    } catch (e) {
      container.innerHTML = `<div class="alert alert-danger">Failed to load games: ${e.message}</div>`;
    }
  }

  function createGameCard(game, category) {
    const tpl = document.getElementById('game-card-template');
    const frag = tpl.content.cloneNode(true);
    const root = frag.querySelector('.game-card');
    const title = frag.querySelector('.game-title');
    const desc = frag.querySelector('.game-description');
    const img = frag.querySelector('.card-img-top');
    const playcount = frag.querySelector('.game-playcount');
    const premium = frag.querySelector('.premium-badge');

    // Get current language from HTML tag
    const currentLang = document.documentElement.getAttribute('lang') || 'en';
    const gameTitle = currentLang === 'ar' && game.title_ar ? game.title_ar : game.Title;
    const gameDesc = currentLang === 'ar' && game.description_ar ? game.description_ar : game.Description;
    
    // Debug logging
    if (currentLang === 'ar') {
     // console.log(`🎮 Game Card: Language=${currentLang}, Title=${game.Title}, Title_AR=${game.title_ar}, Using: ${gameTitle}`);
    }
    
    title.textContent = gameTitle || 'Untitled Game';
    desc.textContent = gameDesc || '';
    // Synthetic metrics for consistent, reasonable numbers per game
    const metrics = getSyntheticGameMetrics(game);
    const playsText = metrics.plays === 1 ? (window.i18n?.t('games.play') || 'play') : (window.i18n?.t('games.plays') || 'plays');
    playcount.textContent = `${formatNumber(metrics.plays)} ${playsText}`;
    
    // Premium badge translation
    if (game.isPremium === 'True') {
      premium.classList.remove('d-none');
      const premiumText = premium.querySelector('span');
      if (premiumText && window.i18n && typeof window.i18n.t === 'function') {
        premiumText.textContent = window.i18n.t('games.premium') || 'Premium';
      }
    }

    applyLazyLoading(img, game.Thumbnail_Large || game.Thumbnail || window.PLACEHOLDER_IMAGE, game.Title || 'Game');

    // Play
    const playBtn = frag.querySelector('.play-btn');
    playBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (game.Content) {
        window.open(game.Content, '_blank');
      }
    });

    // Favorite
    const favBtn = frag.querySelector('.favorite-btn');
    const favIcon = favBtn.querySelector('i');
    function setFavUI(active) {
      favIcon.classList.toggle('bi-heart-fill', active);
      favIcon.classList.toggle('bi-heart', !active);
      favBtn.classList.toggle('text-danger', active);
    }
    // initial state from cookies
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.favorites);
        const active = Array.isArray(list) && list.some(x => String(x.id) === String(game.ID) && x.type === 'games');
        setFavUI(active);
      }
    } catch (e) { /* ignore */ }
    favBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleFavorite === 'function') {
        toggleFavorite('games', game);
        // toggle icon state
        const nowActive = !favIcon.classList.contains('bi-heart-fill');
        setFavUI(nowActive);
      }
    });

    // Nav to detail
    root.addEventListener('click', (e) => {
      if (e.target && e.target.closest && e.target.closest('.action-icon')) return;
      const id = encodeURIComponent(game.ID);
      const cat = encodeURIComponent(category.Name);
      window.location.href = `game-detail.php?id=${id}&category=${cat}`;
    });

    return frag;
  }

  function getSyntheticGameMetrics(game) {
    const key = String(game.ID || game.Id || game.Title || Math.random());
    const plays = clampInt(randFromKey(key + ':plays'), 2500, 980000);
    // Views >= plays, up to +60%
    const views = plays + clampInt(randFromKey(key + ':vdelta'), 0, Math.round(plays * 0.6));
    // Rating between 3.6 and 4.9 (one decimal)
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
})();


