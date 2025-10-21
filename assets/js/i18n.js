/**
 * Internationalization (i18n) module for Streamify Pro
 * Handles language switching and translations
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'streamify_language';
  let currentLanguage = localStorage.getItem(STORAGE_KEY) || 'en';
  let translations = {};
  let languages = [];

  // Load languages and translations
  async function init() {
    try {
      const [langsData, transData] = await Promise.all([
        fetch('./api/json/languages.json').then(r => r.json()),
        fetch('./api/json/translations.json').then(r => r.json())
      ]);

      languages = langsData.languages || [];
      translations = transData;

      // Set initial language
      const savedLang = localStorage.getItem(STORAGE_KEY);
      if (savedLang && languages.some(l => l.code === savedLang)) {
        currentLanguage = savedLang;
      } else {
        currentLanguage = langsData.default || 'en';
      }

      applyLanguage(currentLanguage);
      translatePage();
      setupLanguageSwitcher();
    } catch (e) {
      console.error('Failed to load translations:', e);
    }
  }

  // Get translation for a key
  function t(key) {
    const keys = key.split('.');
    let value = translations;
    
    for (const k of keys) {
      if (value && typeof value === 'object') {
        value = value[k];
      } else {
        return key; // Return key if not found
      }
    }

    if (value && typeof value === 'object' && value[currentLanguage]) {
      return value[currentLanguage];
    }

    return key; // Return key if translation not found
  }

  // Apply language settings (dir, lang attribute)
  function applyLanguage(lang) {
    const language = languages.find(l => l.code === lang);
    if (!language) return;

    document.documentElement.setAttribute('lang', lang);
    document.documentElement.setAttribute('dir', language.dir || 'ltr');
    document.body.setAttribute('data-lang', lang);
    
    currentLanguage = lang;
    localStorage.setItem(STORAGE_KEY, lang);
  }

  // Translate all elements with data-i18n attribute
  function translatePage() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
      const key = element.getAttribute('data-i18n');
      const translation = t(key);
      
      if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
        if (element.hasAttribute('placeholder')) {
          element.placeholder = translation;
        } else {
          element.value = translation;
        }
      } else {
        element.textContent = translation;
      }
    });

    // Translate placeholders separately
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
      const key = element.getAttribute('data-i18n-placeholder');
      element.placeholder = t(key);
    });

    // Translate titles
    document.querySelectorAll('[data-i18n-title]').forEach(element => {
      const key = element.getAttribute('data-i18n-title');
      element.title = t(key);
    });
  }

  // Setup language switcher UI
  function setupLanguageSwitcher() {
    // Setup modal-based language switcher
    setupLanguageModal();
  }

  // Setup language modal functionality
  function setupLanguageModal() {
    const modal = document.getElementById('languageModal');
    if (!modal) return;

    // Update modal state when it's shown
    modal.addEventListener('show.bs.modal', () => {
      updateLanguageModalState();
    });

    // Add click handlers for language options
    modal.querySelectorAll('.language-option').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const lang = btn.getAttribute('data-lang');
        switchLanguage(lang);
        
        // Close modal after selection
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
          modalInstance.hide();
        }
      });
    });
  }

  // Update language modal state
  function updateLanguageModalState() {
    const modal = document.getElementById('languageModal');
    if (!modal) return;

    modal.querySelectorAll('.language-option').forEach(btn => {
      const lang = btn.getAttribute('data-lang');
      if (lang === currentLanguage) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  // Update switcher state (legacy function - kept for compatibility)
  function updateSwitcherState(switcher) {
    if (!switcher) return;
    switcher.querySelectorAll('[data-lang]').forEach(btn => {
      if (btn.getAttribute('data-lang') === currentLanguage) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  // Switch to a different language
  function switchLanguage(lang) {
    if (lang === currentLanguage) return;
    
    applyLanguage(lang);
    translatePage();
    
    // Update modal state
    updateLanguageModalState();

    // Trigger custom event
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
    
    // Reload dynamic content if needed
    if (typeof window.reloadPageContent === 'function') {
      window.reloadPageContent();
    }
  }

  // Get current language
  function getCurrentLanguage() {
    return currentLanguage;
  }

  // Get current language direction
  function getCurrentDirection() {
    const language = languages.find(l => l.code === currentLanguage);
    return language ? language.dir : 'ltr';
  }

  // Expose API
  window.i18n = {
    init,
    t,
    switchLanguage,
    translatePage,
    getCurrentLanguage,
    getCurrentDirection
  };

  // Auto-initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

