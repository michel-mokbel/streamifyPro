/**
 * Simple i18n - ONLY translates text, NO switching logic!
 * Language is set by PHP session, we just translate the page
 */
(function () {
  'use strict';

  let translations = {};
  const currentLang = document.documentElement.getAttribute('lang') || 'en';

  // Load translations and translate page
  async function init() {
    try {
      translations = await fetch('./api/json/translations.json').then(r => r.json());
     // console.log('✅ i18n: Translations loaded. Current language:', currentLang);
      translatePage();
    //  console.log('✅ i18n: Page translated');
    } catch (e) {
      console.error('❌ i18n: Failed to load translations:', e);
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
        return key;
      }
    }

    if (value && typeof value === 'object' && value[currentLang]) {
      return value[currentLang];
    }

    return key;
  }

  // Translate all elements with data-i18n attribute
  function translatePage() {
    const elements = document.querySelectorAll('[data-i18n]');
   // console.log(`🔤 i18n: Found ${elements.length} elements to translate`);
    
    let translatedCount = 0;
    elements.forEach(element => {
      const key = element.getAttribute('data-i18n');
      const translation = t(key);
      
      if (translation && translation !== key) {
        translatedCount++;
        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
          if (element.hasAttribute('placeholder')) {
            element.placeholder = translation;
          } else {
            element.value = translation;
          }
        } else {
          element.textContent = translation;
        }
      }
    });
    //console.log(`✅ i18n: Translated ${translatedCount}/${elements.length} elements`);

    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
      const key = element.getAttribute('data-i18n-placeholder');
      element.placeholder = t(key);
    });

    document.querySelectorAll('[data-i18n-title]').forEach(element => {
      const key = element.getAttribute('data-i18n-title');
      element.title = t(key);
    });
  }

  // Helper functions to get language info
  function getCurrentLanguage() {
    return document.documentElement.getAttribute('lang') || 'en';
  }

  function getCurrentDirection() {
    return document.documentElement.getAttribute('dir') || 'ltr';
  }

  // Expose simple API
  window.i18n = { t, translatePage, getCurrentLanguage, getCurrentDirection };

  // Auto-initialize
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
