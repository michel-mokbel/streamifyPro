/** Page: Kids (channels list) */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', init);
  
  // Listen for language changes and reload content
  window.addEventListener('languageChanged', init);

  async function init() {
    const grid = document.getElementById('kids-channels');
    if (!grid) return;
    const data = await fetchKids();
    renderChannels(grid, data.channels || []);
  }

  async function fetchKids() {
    // Get current language from i18n or localStorage
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    const res = await fetch(`./api/api.php?route=kids&lang=${currentLang}`);
    if (!res.ok) throw new Error('Failed to load kids');
    return res.json();
  }

  function renderChannels(container, channels) {
    container.innerHTML = '';
    const currentLang = (window.i18n && window.i18n.currentLanguage) || localStorage.getItem('streamify_language') || 'en';
    
    channels.forEach(ch => {
      // Use translated fields if available (for Arabic), otherwise use original
      const channelName = (currentLang === 'ar' && ch.name_ar) ? ch.name_ar : ch.name;
      const channelDesc = (currentLang === 'ar' && ch.description_ar) ? ch.description_ar : (ch.description || '');
      
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-xl-4';
      col.innerHTML = `
        <div class="card shadow-sm h-100">
          <img src="${ch.bannerImage || ''}" class="card-img-top" alt="${channelName}" style="height: 180px; object-fit: cover;">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center mb-2">
              <img src="${ch.profileImage || ''}" alt="${channelName}" width="40" height="40" class="rounded me-2" />
              <h5 class="card-title mb-0">${channelName}</h5>
            </div>
            <p class="card-text small text-muted text-truncate-3">${channelDesc}</p>
            <a class="btn btn-outline-primary mt-auto" href="kids-channel.php?id=${encodeURIComponent(ch.id)}">View Channel</a>
          </div>
        </div>`;
      container.appendChild(col);
    });
  }
})();


