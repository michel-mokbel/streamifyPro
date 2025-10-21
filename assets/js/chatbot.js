// Streamify Pro - Floating Chat Modal (i18n, theming, thinking indicator)
(function(){
  const API = '/api/llm_agent.php';
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

  const state = {
    lang: detectLang(),
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
          // Also update dir if provided
          if (window.StreamifyTranslations.dir && window.StreamifyTranslations.dir[state.lang]){
            state.dir = window.StreamifyTranslations.dir[state.lang];
            document.documentElement.setAttribute('dir', state.dir);
          }
          return;
        }
      }
    }catch(_){}

    try{
      // Fetch languages.json to confirm dir for chosen lang
      const langRes = await fetch(LANG_JSON);
      if (langRes.ok){
        const langs = await langRes.json();
        const list = langs.languages || langs;
        const found = (list || []).find(l => (l.code||'').toLowerCase() === state.lang);
        if (found && found.dir) {
          state.dir = found.dir;
          document.documentElement.setAttribute('dir', state.dir);
        }
      }
    }catch(_){/* ignore */}

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
  modal.innerHTML = `
    <div id="sp-chat-header">
      <div>
        <div id="sp-chat-title"></div>
        <div id="sp-chat-sub"></div>
      </div>
      <button id="sp-close" style="background:none;border:none;color:#fff;font-size:22px;line-height:1" aria-label="Close">×</button>
    </div>
    <div id="sp-toolbar">
      <div style="display:flex; gap:8px; align-items:center;">
        <label style="font-size:12px">${/* age */''}<span id="sp-age-label"></span></label>
        <input id="sp-age" type="number" min="2" max="14" value="6" style="width:64px">
        <label style="font-size:12px"><span id="sp-min-label"></span></label>
        <input id="sp-minutes" type="number" min="5" max="120" step="5" value="20" style="width:72px">
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
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
    document.body.appendChild(modal);

    // Set labels/translations
    $('#sp-chat-title').textContent = state.i18n.title;
    $('#sp-chat-sub').textContent = state.i18n.subtitle;
    $('#sp-chat-input').placeholder = state.i18n.placeholder;
    $('#sp-send-btn').textContent = state.i18n.send;
    $('#sp-age-label').textContent = state.i18n.age + ':';
    $('#sp-min-label').textContent = state.i18n.minutes + ':';
    $('#sp-lang-label').textContent = state.i18n.language + ':';
    $('#sp-lang').value = state.lang;

    // RTL handling
    if (state.dir === 'rtl') {
      document.documentElement.setAttribute('dir','rtl');
    }

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
          label: isArabic ? 'حروف الأبجدية' : 'Alphabet Videos',
          makePrompt: (s) => isArabic ? 'أريد قائمة تشغيل للحروف الأبجدية' : 'Show me alphabet videos playlist'
        },
        {
          key: 'chip_numbers',
          label: isArabic ? 'الأرقام والرياضيات' : 'Numbers & Math',
          makePrompt: (s) => isArabic ? 'أريد فيديوهات عن الأرقام والرياضيات' : 'I want numbers and math videos'
        },
        {
          key: 'chip_animals',
          label: isArabic ? 'الحيوانات والطبيعة' : 'Animals & Nature',
          makePrompt: (s) => isArabic ? 'أريد فيديوهات عن الحيوانات' : 'Show me videos about animals'
        },
        {
          key: 'chip_games',
          label: isArabic ? 'ألعاب تفاعلية' : 'Interactive Games',
          makePrompt: (s) => isArabic ? 'أريد ألعاب تفاعلية' : 'Suggest interactive games'
        },
        {
          key: 'chip_music',
          label: isArabic ? 'أغاني ومقاطع موسيقية' : 'Music & Songs',
          makePrompt: (s) => isArabic ? 'أريد أغاني تعليمية' : 'Show me educational songs'
        },
        {
          key: 'chip_fitness',
          label: isArabic ? 'تمارين رياضية' : 'Fitness & Exercise',
          makePrompt: (s) => isArabic ? 'أريد تمارين رياضية' : 'Give me fitness exercises'
        },
        {
          key: 'chip_stories',
          label: isArabic ? 'قصص وحكايات' : 'Stories',
          makePrompt: (s) => isArabic ? 'أريد قصص تعليمية' : 'I want educational stories'
        },
        {
          key: 'chip_science',
          label: isArabic ? 'علوم واكتشافات' : 'Science',
          makePrompt: (s) => isArabic ? 'أريد فيديوهات علمية' : 'Show me science videos'
        }
      ];
    }

    const toggle = () => {
      modal.style.display = (modal.style.display === 'block') ? 'none' : 'block';
      if (modal.style.display === 'block') input.focus();
    };
    btn.addEventListener('click', toggle);
    close.addEventListener('click', toggle);

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
          body: JSON.stringify({ message: text, language: state.lang, age: state.age, minutes: state.minutes })
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

    // Rebuild chips when controls change
    ['change','input'].forEach(evt => {
      langSel.addEventListener(evt, ()=>{ state.lang = langSel.value; buildChips(); });
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