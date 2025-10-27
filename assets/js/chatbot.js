// Streamify Pro - Floating Chat Modal (i18n, theming, thinking indicator)
(function(){
  const API = '/streamifyPro/api/chatbot.php';
  const LANG_JSON = '/api/json/languages.json';
  const TRANSLATIONS_JSON = '/api/json/translations.json';

  // Basic i18n keys for the chat UI
  const I18N_DEFAULT = {
    en: {
      title: "Assistant",
      subtitle: "Kids suggestions & playlists",
      placeholder: "Ask for suggestions or a playlist…",
      send: "Send",
      open: "Open",
      greeting: "Hi! I can suggest videos or games based on topics you're interested in.",
      tip: 'Try: "Show me alphabet videos" or "I want interactive games"',
      thinking: "Thinking…",
      minutes: "Minutes",
      age: "Age",
      language: "Language",
      network_error: "Network error. Please try again.",
    },
    ar: {
      title: "المساعد",
      subtitle: "اقتراحات للأطفال وقوائم تعليمية",
      placeholder: "اطلب اقتراحات أو قائمة تعليمية…",
      send: "إرسال",
      open: "فتح",
      greeting: "مرحبًا! يمكنني اقتراح مقاطع فيديو أو ألعاب بناءً على المواضيع التي تهتم بها.",
      tip: 'جرّب: "أريد فيديوهات الحروف الأبجدية" أو "أريد ألعاب تفاعلية"',
      thinking: "يفكر…",
      minutes: "الدقائق",
      age: "العمر",
      language: "اللغة",
      network_error: "خطأ في الشبكة. حاول مرة أخرى.",
    }
  };

  // Utilities
  const $$ = (sel, ctx=document) => ctx.querySelector(sel);

  function readPersist(key){
    try { const v = localStorage.getItem(key); if (v!=null) return JSON.parse(v); } catch(_){}
    try { const v = sessionStorage.getItem(key); if (v!=null) return JSON.parse(v); } catch(_){}
    return null;
  }
  function writePersist(key, value){
    try { localStorage.setItem(key, JSON.stringify(value)); } catch(_){}
    try { sessionStorage.setItem(key, JSON.stringify(value)); } catch(_){}
  }

  // Read language from HTML (set by PHP session) - DO NOT override!
  const state = {
    lang: document.documentElement.getAttribute('lang') || 'en',
    dir: document.documentElement.getAttribute('dir') || 'ltr',
    i18n: I18N_DEFAULT.en,
    age: 6,
    minutes: 20
  };

  // Theme primary color from Bootstrap .btn-primary (fallback to CSS var if not found)
  function detectPrimaryColor(){
    const probe = document.createElement('button');
    probe.className = 'btn btn-primary';
    probe.style.position = 'absolute'; probe.style.opacity='0'; probe.style.pointerEvents='none';
    document.body.appendChild(probe);
    const bg = getComputedStyle(probe).backgroundColor || '';
    document.body.removeChild(probe);
    return bg || 'rgb(11,94,215)';
  }

  function applyTheme(){
    const color = detectPrimaryColor();
    const root = document.documentElement;
    root.style.setProperty('--sp-primary', color);
  }

  function detectLang(){
    // Try main i18n system key first, then chatbot key for backward compatibility
    const mainLang = readPersist('streamify_language');
    if (mainLang && typeof mainLang === 'string') return mainLang.toLowerCase();
    const persisted = readPersist('sp_ui_lang');
    if (persisted && typeof persisted === 'string') return persisted.toLowerCase();
    const htmlLang = (document.documentElement.getAttribute('lang') || '').split('-')[0];
    if (htmlLang) return htmlLang.toLowerCase();
    // fallback: try to infer from body classes or other hints
    return 'en';
  }

  async function loadI18n(){
    // Hook to site-wide translations if available
    try{
      if (window.StreamifyTranslations && window.StreamifyTranslations.chat){
        const langPack = window.StreamifyTranslations.chat[state.lang];
        if (langPack && typeof langPack === 'object'){
          // Merge known keys only
          state.i18n = Object.assign({}, I18N_DEFAULT.en, I18N_DEFAULT[state.lang] || {}, langPack);
          // Read dir from HTML (set by PHP)
          state.dir = document.documentElement.getAttribute('dir') || 'ltr';
          return;
        }
      }
    }catch(_){}

    // Read direction from HTML (set by PHP session)
    state.dir = document.documentElement.getAttribute('dir') || 'ltr';

    // Load translations.json if present; merge minimal keys we use
    try{
      const trRes = await fetch(TRANSLATIONS_JSON);
      if (trRes.ok){
        const tr = await trRes.json();
        // Expect format: { en: {...}, ar: {...} } or a flat map - we only use our keys
        const langPack = tr[state.lang] || {};
        state.i18n = Object.assign({}, I18N_DEFAULT.en, I18N_DEFAULT[state.lang] || {}, pickKeys(langPack, Object.keys(I18N_DEFAULT.en)));
        return;
      }
    }catch(_){/* ignore */}
    // Fallback to embedded
    state.i18n = Object.assign({}, I18N_DEFAULT.en, I18N_DEFAULT[state.lang] || {});
  }

  function pickKeys(src, keys){
    const out = {};
    keys.forEach(k => { if (src && typeof src[k] === 'string') out[k] = src[k]; });
    return out;
  }

  // Build UI
  const btn = document.createElement('button');
  btn.id='sp-chat-toggle';
  btn.title='Ask Streamify Assistant';
  btn.innerHTML='💬';

  const modal = document.createElement('div');
  modal.id='sp-chat-modal';
  const backdrop = document.createElement('div');
  backdrop.id='sp-chat-backdrop';
  modal.innerHTML = `
    <div id="sp-chat-header" style= "display: none">
      <div>
        <div id="sp-chat-title"></div>
        <div id="sp-chat-sub"></div>
      </div>
      <button id="sp-close" style="background:none;border:none;color:#fff;font-size:22px;line-height:1" aria-label="Close">×</button>
    </div>
    <div id="sp-toolbar" style= "display: none">
      <div style="display:none;">
        <label style="font-size:12px">${/* age */''}<span id="sp-age-label"></span></label>
        <input id="sp-age" type="number" min="2" max="14" value="6" style="width:64px">
        <label style="font-size:12px"><span id="sp-min-label"></span></label>
        <input id="sp-minutes" type="number" min="5" max="120" step="5" value="20" style="width:72px">
      </div>
      <div style="display:none; gap:8px; align-items:center; justify-content:flex-end;">
        <label style="font-size:12px"><span id="sp-lang-label"></span></label>
        <select id="sp-lang" style="min-width:90px">
          <option value="en">English</option>
          <option value="ar">العربية</option>
        </select>
      </div>
    </div>
    <div id="sp-chips" style="padding:8px 12px; display:flex; flex-wrap:wrap; gap:8px; border-top:1px solid var(--sp-border); background: var(--sp-surface);"></div>
    <div id="sp-chat-body"></div>
    <div id="sp-chat-inputbar">
      <input id="sp-chat-input" placeholder="" />
      <button id="sp-send-btn"></button>
    </div>
  `;

  const style = document.createElement('link');
  style.rel='stylesheet';
  style.href='./assets/css/chatbot.css';

  document.addEventListener('DOMContentLoaded', async () => {
    applyTheme();
    await loadI18n();

    // Inject
    document.head.appendChild(style);
    document.body.appendChild(btn);
    document.body.appendChild(backdrop);
    document.body.appendChild(modal);

    // Ensure modal uses flex for column layout defined in CSS
    const modalEl = $('#sp-chat-modal');
    if (modalEl) modalEl.style.display = 'none';

    // Set labels/translations
    $('#sp-chat-title').textContent = state.i18n.title;
    $('#sp-chat-sub').textContent = state.i18n.subtitle;
    $('#sp-chat-input').placeholder = state.i18n.placeholder;
    $('#sp-send-btn').textContent = state.i18n.send;
    $('#sp-age-label').textContent = state.i18n.age + ':';
    $('#sp-min-label').textContent = state.i18n.minutes + ':';
    $('#sp-lang-label').textContent = state.i18n.language + ':';
    $('#sp-lang').value = state.lang;

    // Read direction from HTML (set by PHP)
    state.dir = document.documentElement.getAttribute('dir') || 'ltr';

    wire();
  });

  function $(sel, ctx=document){ return ctx.querySelector(sel); }

  function wire(){
    const body = $('#sp-chat-body');
    const input = $('#sp-chat-input');
    const send = $('#sp-send-btn');
    const close = $('#sp-close');
    const langSel = $('#sp-lang');
    const ageInput = $('#sp-age');
    const minInput = $('#sp-minutes');

    state.age = parseInt(ageInput.value || '6', 10);
    state.minutes = parseInt(minInput.value || '20', 10);


    const buildChips = () => {
      const chips = $('#sp-chips');
      chips.innerHTML = '';
      const presets = getChipPresets();
      presets.forEach(p => {
        const b = document.createElement('button');
        b.type='button';
        b.className = 'btn btn-sm';
        b.style.border = '1px solid var(--sp-border)';
        b.style.background = '#fff';
        b.style.borderRadius = '999px';
        b.style.padding = '6px 10px';
        b.style.fontSize = '12px';
        b.textContent = p.label;
        b.addEventListener('click', () => {
          // Directly send the formatted prompt
          $('#sp-chat-input').value = p.makePrompt(state);
          ask();
        });
        chips.appendChild(b);
      });
    };

  function getChipPresets(){
    const t = state.i18n;
    const isArabic = state.lang === 'ar';

    return [
      {
        key: 'chip_alphabet',
        label: isArabic ? 'حروف الأبجدية' : 'Alphabet Songs',
        makePrompt: (s) => isArabic ? 'حروف الأبجدية' : 'Alphabet songs'
      },
      {
        key: 'chip_animals',
        label: isArabic ? 'فيديوهات الحيوانات' : 'Animal Videos',
        makePrompt: (s) => isArabic ? 'فيديوهات عن الحيوانات' : 'Animal videos'
      },
      {
        key: 'chip_games',
        label: isArabic ? 'ألعاب للأطفال' : 'Games for Kids',
        makePrompt: (s) => isArabic ? 'ألعاب للأطفال' : 'Games for kids'
      },
      {
        key: 'chip_stories',
        label: isArabic ? 'قصص قبل النوم' : 'Bedtime Stories',
        makePrompt: (s) => isArabic ? 'قصص قبل النوم' : 'Bedtime stories'
      },
      {
        key: 'chip_numbers',
        label: isArabic ? 'تعلم الأرقام' : 'Learn Numbers',
        makePrompt: (s) => isArabic ? 'تعلم الأرقام' : 'Learn numbers'
      },
      {
        key: 'chip_dance',
        label: isArabic ? 'رقص وحركة' : 'Dance & Movement',
        makePrompt: (s) => isArabic ? 'فيديوهات رقص' : 'Dance videos'
      },
      {
        key: 'chip_science',
        label: isArabic ? 'علوم للأطفال' : 'Science for Kids',
        makePrompt: (s) => isArabic ? 'علوم للأطفال' : 'Science for kids'
      },
      {
        key: 'chip_music',
        label: isArabic ? 'أغاني تعليمية' : 'Educational Songs',
        makePrompt: (s) => isArabic ? 'أغاني تعليمية' : 'Educational songs'
      }
    ];
  }

    const setOpen = (open) => {
      modal.style.display = open ? 'flex' : 'none';
      backdrop.style.display = open ? 'block' : 'none';
      if (open) {
        // ensure chips visible at top of modal
        const chips = $('#sp-chips');
        if (chips) chips.scrollLeft = 0;
        input.focus();
      }
    };
    const toggle = () => {
      const open = (modal.style.display !== 'flex');
      setOpen(open);
    };
    btn.addEventListener('click', toggle);
    close.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));

    const push = (text, who='user') => {
      const msg = document.createElement('div');
      msg.className = 'sp-msg ' + who;
      const bb = document.createElement('div');
      bb.className = 'sp-bubble';
      bb.textContent = text;
      msg.appendChild(bb);
      body.appendChild(msg);
      body.scrollTop = body.scrollHeight;
      return msg;
    };

    const pushTyping = () => {
      const msg = document.createElement('div');
      msg.className = 'sp-msg bot sp-typing';
      const bb = document.createElement('div');
      bb.className = 'sp-bubble';
      bb.innerHTML = `<span>${escapeHtml(state.i18n.thinking)} </span><span class="sp-dots"><span></span><span></span><span></span></span>`;
      msg.appendChild(bb);
      body.appendChild(msg);
      body.scrollTop = body.scrollHeight;
      return msg;
    };

    const pushItems = (payload) => {
      const { summary, items = [] } = payload || {};
      if (summary) push(summary, 'bot');
      items.forEach(it => {
        const card = document.createElement('div');
        card.className = 'sp-item-card';
        const metaL = [];
        if (it.type) metaL.push((it.type||'').toUpperCase());
        if (it.duration_sec) metaL.push(Math.round(it.duration_sec/60)+' min');
        const meta = `<div class="meta">${metaL.join(' • ')}</div>`;
        const img = it.thumbnail ? `<img src="${it.thumbnail}" alt="">` : '';
        const detailUrl = it.detail_url || it.content_url || '#';
        card.innerHTML = `<h5>${escapeHtml(it.title||'')}</h5>${meta}${img}<div style="margin-top:8px"><a href="${escapeHtml(detailUrl)}">${escapeHtml(state.i18n.open)}</a></div>`;
        body.appendChild(card);
      });
      body.scrollTop = body.scrollHeight;
    };

    // Quick greeting + tip
    push(state.i18n.greeting, 'bot');
    push(state.i18n.tip, 'bot');

    async function ask(){
      const text = input.value.trim();
      if (!text) return;
      state.lang = langSel.value || state.lang;
      state.age = parseInt(ageInput.value || state.age, 10);
      state.minutes = parseInt(minInput.value || state.minutes, 10);

      push(text, 'user');
      input.value = '';

      // disable send + show typing
      send.disabled = true;
      const typing = pushTyping();

      try{
        const res = await fetch(API, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ message: text, max_items: 8, debug: false })
        });
        const json = await res.json();
        // remove typing
        typing.remove();
        if (json.error) {
          push('Error: ' + json.error, 'bot');
        } else if (json.text) {
          // Some Gemini responses might be wrapped
          try { 
            const parsedText = JSON.parse(json.text);
            pushItems(parsedText);
            // If parsed text has empty items, show error message
            if (parsedText.items && Array.isArray(parsedText.items) && parsedText.items.length === 0) {
              push('Sorry, no content was found. Please try asking differently.', 'bot');
            }
          }
          catch { push(json.text, 'bot'); }
        } else {
          pushItems(json);
        }
      }catch(e){
        typing.remove();
        push(state.i18n.network_error, 'bot');
      } finally {
        send.disabled = false;
      }
    };

    send.addEventListener('click', () => ask());
    input.addEventListener('keydown', (e)=>{ if(e.key==='Enter') ask(); });

    // Language change - redirect through PHP to update session
    langSel.addEventListener('change', ()=>{ 
      const newLang = langSel.value;
      if (newLang && newLang !== state.lang) {
        window.location.href = `set_language.php?lang=${newLang}`;
      }
    });
    
    // Rebuild chips when other controls change
    ['change','input'].forEach(evt => {
      ageInput.addEventListener(evt, ()=>{ state.age = parseInt(ageInput.value||'6',10); buildChips(); });
      minInput.addEventListener(evt, ()=>{ state.minutes = parseInt(minInput.value||'20',10); buildChips(); });
    });

    // Initial chips
    buildChips();
  }

  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }
})();