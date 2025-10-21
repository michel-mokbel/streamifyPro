/** Page: Streaming (multi-page version) */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", init);

  // Re-initialize when language changes
  window.addEventListener("languageChanged", init);

  async function init() {
    const container = document.getElementById("streaming-content");
    if (!container) return;

    try {
      // Get current language
      const currentLang = localStorage.getItem("streamify_language") || "en";
      const data = await fetchData("streaming", currentLang);

      const bubbles = document.getElementById("streaming-category-bubbles");
      const sectionsWrapper = document.createElement("div");
      sectionsWrapper.className = "category-sections";
      sectionsWrapper.style.maxWidth = "1200px";
      sectionsWrapper.style.margin = "0 auto";
      container.innerHTML = "";
      container.appendChild(sectionsWrapper);

      const INITIAL_VIDEOS_TO_SHOW = 12;
      let first = true;

      (data.Content || []).forEach((group) => {
        (group.Videos || []).forEach((category) => {
          if (!category.Content || category.Content.length === 0) return;

          const categoryId = `streaming-category-${category.Name.toLowerCase().replace(
            /\s+/g,
            "-"
          )}`;

          // Get category name (Arabic if available)
          const categoryName =
            currentLang === "ar" && category.name_ar
              ? category.name_ar
              : category.Name;

          // Bubble
          const bubble = document.createElement("div");
          bubble.className = `category-bubble ${first ? "active" : ""}`;
          bubble.setAttribute("data-category-id", categoryId);
          // if (category.Icon) {
          //   const ic = document.createElement("img");
          //   ic.src = category.Icon;
          //   ic.alt = categoryName;
          //   bubble.appendChild(ic);
          // }
          const label = document.createElement("span");
          label.textContent = categoryName;
          bubble.appendChild(label);
          bubbles.appendChild(bubble);

          // Section
          const section = document.createElement("div");
          section.className = `category-section ${first ? "active" : ""}`;
          section.id = categoryId;
          const grid = document.createElement("div");
          grid.className = "category-videos";
          section.appendChild(grid);

          category.Content.slice(0, INITIAL_VIDEOS_TO_SHOW).forEach((video) =>
            grid.appendChild(createVideoCard(video, category))
          );

          // Load more
          if (category.Content.length > INITIAL_VIDEOS_TO_SHOW) {
            const loadWrap = document.createElement("div");
            loadWrap.className = "text-center w-100 mt-3 mb-4";
            const btn = document.createElement("button");
            btn.className = "btn btn-outline-primary";
            btn.textContent = `Load More (${
              category.Content.length - INITIAL_VIDEOS_TO_SHOW
            } more)`;
            let index = INITIAL_VIDEOS_TO_SHOW;
            btn.addEventListener("click", () => {
              const next = category.Content.slice(
                index,
                index + INITIAL_VIDEOS_TO_SHOW
              );
              next.forEach((v) =>
                grid.appendChild(createVideoCard(v, category))
              );
              index += next.length;
              if (index >= category.Content.length) loadWrap.remove();
              else
                btn.textContent = `Load More (${
                  category.Content.length - index
                } more)`;
            });
            loadWrap.appendChild(btn);
            section.appendChild(loadWrap);
          }

          sectionsWrapper.appendChild(section);
          first = false;
        });
      });

      const allBubbles = bubbles.querySelectorAll(".category-bubble");
      allBubbles.forEach((b) =>
        b.addEventListener("click", () => {
          const id = b.getAttribute("data-category-id");
          allBubbles.forEach((x) => x.classList.remove("active"));
          b.classList.add("active");
          document
            .querySelectorAll(".category-section")
            .forEach((s) => s.classList.toggle("active", s.id === id));
        })
      );

      // Category bar prev/next buttons
      const navContainer = document.querySelector(".category-nav-container");
      const scroller = navContainer
        ? navContainer.querySelector(".category-nav-scroll")
        : null;
      const btnPrev = navContainer
        ? navContainer.querySelector(".category-nav-prev")
        : null;
      const btnNext = navContainer
        ? navContainer.querySelector(".category-nav-next")
        : null;
      if (scroller && btnPrev && btnNext) {
        const isRTL =
          (
            document.documentElement.getAttribute("dir") || "ltr"
          ).toLowerCase() === "rtl";
        const getStep = () =>
          Math.max(200, Math.floor(scroller.clientWidth * 0.9));
        btnPrev.addEventListener("click", () => {
          const dx = getStep();
          scroller.scrollBy({ left: isRTL ? dx : -dx, behavior: "smooth" });
        });
        btnNext.addEventListener("click", () => {
          const dx = getStep();
          scroller.scrollBy({ left: isRTL ? -dx : dx, behavior: "smooth" });
        });
      }
    } catch (e) {
      container.innerHTML = `<div class="alert alert-danger">Failed to load streaming: ${e.message}</div>`;
    }
  }

  function createVideoCard(video, category) {
    const wrap = document.createElement("div");
    wrap.className = "streaming-card-wrapper";
    const viewsText = window.i18n?.t("streaming.views") || "views";
    const favoriteText = window.i18n?.t("streaming.favorite") || "Favorite";
    const watchLaterText =
      window.i18n?.t("streaming.watchLater") || "Watch Later";

    // Get current language and use Arabic translations if available
    const currentLang = localStorage.getItem("streamify_language") || "en";
    const videoTitle =
      currentLang === "ar" && video.title_ar ? video.title_ar : video.Title;

    wrap.innerHTML = `
      <div class="card shadow-sm h-100">
        <div class="position-relative">
          <img class="card-img-top" alt="" style="height: 180px; object-fit: cover;" />
          <div class="thumb-play">
            <i class="bi bi-play-fill fs-4"></i>
          </div>
        </div>
        <div class="card-body p-3">
          <h5 class="card-title fs-6 text-truncate">${
            videoTitle || "Untitled"
          }</h5>
          <p class="card-text small text-muted mb-0">${formatNumber(
            getSyntheticVideoMetrics(video).views
          )} ${viewsText}</p>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-end align-items-center p-3">
          <div class="d-flex">
            <button class="action-icon me-2 favorite-btn" title="${favoriteText}"><i class="bi bi-heart"></i></button>
            <button class="action-icon watch-later-btn" title="${watchLaterText}"><i class="bi bi-clock"></i></button>
          </div>
        </div>
      </div>`;

    const img = wrap.querySelector("img");
    applyLazyLoading(
      img,
      video.Thumbnail || video.Thumbnail_Large || window.PLACEHOLDER_IMAGE,
      video.Title || "Video"
    );

    // Click: go to detail page (ignore clicks on action buttons)
    wrap.querySelector(".card").addEventListener("click", (e) => {
      if (e.target && e.target.closest && e.target.closest(".action-icon"))
        return;
      const id = encodeURIComponent(video.Id || video.ID);
      const cat = encodeURIComponent(category?.Name || "");
      window.location.href = `video-detail.php?id=${id}&category=${cat}`;
    });

    // Favorite button
    const favBtn = wrap.querySelector(".favorite-btn");
    const favIcon = favBtn.querySelector("i");
    function setFavUI(active) {
      favBtn.classList.toggle("text-danger", active);
      favIcon.classList.toggle("bi-heart-fill", active);
      favIcon.classList.toggle("bi-heart", !active);
    }
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.favorites);
        const active =
          Array.isArray(list) &&
          list.some(
            (x) =>
              String(x.id) === String(video.Id || video.ID) &&
              x.type === "streaming"
          );
        setFavUI(active);
      }
    } catch (_) {}
    favBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleFavorite === "function") {
        toggleFavorite("streaming", {
          Id: video.Id || video.ID,
          Title: video.Title,
          Thumbnail: video.Thumbnail || video.Thumbnail_Large,
          Content: video.Content || "",
        });
        const nowActive = !favIcon.classList.contains("bi-heart-fill");
        setFavUI(nowActive);
      }
    });

    // Watch later button
    const wlBtn = wrap.querySelector(".watch-later-btn");
    const wlIcon = wlBtn.querySelector("i");
    function setWlUI(active) {
      wlBtn.classList.toggle("active", active);
      wlIcon.classList.toggle("bi-clock-fill", active);
      wlIcon.classList.toggle("bi-clock", !active);
    }
    try {
      if (window.readCookieList && window.COOKIE_KEYS) {
        const list = readCookieList(COOKIE_KEYS.watchLater);
        const active =
          Array.isArray(list) &&
          list.some(
            (x) =>
              String(x.id) === String(video.Id || video.ID) &&
              x.type === "streaming"
          );
        setWlUI(active);
      }
    } catch (_) {}
    wlBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      if (typeof toggleWatchLater === "function") {
        toggleWatchLater("streaming", {
          Id: video.Id || video.ID,
          Title: video.Title,
          Thumbnail: video.Thumbnail || video.Thumbnail_Large,
          Content: video.Content || "",
        });
        const nowActive = !wlIcon.classList.contains("bi-clock-fill");
        setWlUI(nowActive);
      }
    });

    return wrap;
  }

  // Synthetic metrics for streaming videos (deterministic per video)
  function getSyntheticVideoMetrics(video) {
    const key = String(video.Id || video.ID || video.Title || Math.random());
    const views = clampInt(randFromKey(key + ":views"), 5000, 3000000); // 5K - 3M
    const ratingRaw =
      3.6 + (randFromKey(key + ":rating") / 4294967295) * (4.9 - 3.6);
    const rating = Math.round(ratingRaw * 10) / 10;
    return { views, rating };
  }

  function randFromKey(key) {
    let h = 2166136261 >>> 0; // FNV-1a
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
