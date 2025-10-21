document.addEventListener('DOMContentLoaded', async () => {
  try {
    const [streaming, games, kids, fitness] = await Promise.all([
      fetch('./api/api.php?route=streaming').then(r => r.json()).catch(() => ({})),
      fetch('./api/api.php?route=games').then(r => r.json()).catch(() => ({})),
      fetch('./api/api.php?route=kids').then(r => r.json()).catch(() => ({})),
      fetch('./api/json/fitness-ar.json').then(r => r.json()).catch(() => ({})),
    ]);

    const streamingCount = (streaming.Content || []).reduce((acc, g) => acc + ((g.Videos || []).reduce((a, c) => a + ((c.Content || []).length || 0), 0)), 0);
    const gamesCount = (games.Content || []).reduce((acc, g) => acc + ((g.HTML5 || []).reduce((a, c) => a + ((c.Content || []).length || 0), 0)), 0);
    const kidsCount = (kids.channels || []).reduce((acc, ch) => acc + ((ch.playlists || []).reduce((a, p) => a + ((p.content || []).length || 0), 0)), 0);
    const fitnessCount = (fitness.videos || []).length;

    const s = document.getElementById('streamingCount'); if (s) s.textContent = streamingCount.toLocaleString();
    const g = document.getElementById('gamesCount'); if (g) g.textContent = gamesCount.toLocaleString();
    const k = document.getElementById('kidsCount'); if (k) k.textContent = kidsCount.toLocaleString();
    const f = document.getElementById('fitnessCount'); if (f) f.textContent = fitnessCount.toLocaleString();
  } catch (e) {
    // ignore counts on error
  }
});


