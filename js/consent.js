/* =========================================
   SOMFAI KLÍMATECHNIKA – Cookie-hozzájárulás
   Google Consent Mode v2. A GTM-snippet ELŐTT kell betölteni a <head>-ben.
   ========================================= */

(function () {
  'use strict';

  var STORAGE_KEY = 'somfai_consent';
  var VERSION     = 1; // növeld, ha változik a hozzájárulási szöveg → újra megkérdezi a látogatót
  var POLICY_URL  = 'adatkezelesi-tajekoztato.html';

  // ---- Consent Mode: alapértelmezés = minden elutasítva ----
  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }

  gtag('consent', 'default', {
    analytics_storage:  'denied',
    ad_storage:         'denied',
    ad_user_data:       'denied',
    ad_personalization: 'denied',
    wait_for_update:    500
  });

  // ---- Tárolt választás ----
  function load() {
    try {
      var saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
      if (saved && saved.version === VERSION) return saved;
    } catch (e) { /* tiltott tárolás: újra megkérdezzük */ }
    return null;
  }

  function save(statistics, marketing) {
    var choice = { version: VERSION, statistics: !!statistics, marketing: !!marketing, ts: Date.now() };
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(choice)); } catch (e) { /* nem kritikus */ }
    apply(choice);
    return choice;
  }

  function apply(choice) {
    var stat = choice.statistics ? 'granted' : 'denied';
    var mkt  = choice.marketing  ? 'granted' : 'denied';
    gtag('consent', 'update', {
      analytics_storage:  stat,
      ad_storage:         mkt,
      ad_user_data:       mkt,
      ad_personalization: mkt
    });
  }

  var saved = load();
  if (saved) apply(saved);

  // ---- Felület ----
  var banner, panel, lastFocus;

  function el(tag, attrs, html) {
    var n = document.createElement(tag);
    for (var k in attrs) n.setAttribute(k, attrs[k]);
    if (html) n.innerHTML = html;
    return n;
  }

  function buildBanner() {
    banner = el('div', { class: 'cc-banner', role: 'region', 'aria-label': 'Cookie-beállítások', hidden: '' },
      '<div class="cc-text">' +
        '<strong>Sütiket (cookie-kat) használunk</strong>' +
        '<p>A weboldal működéséhez szükséges sütik mellett – az Ön hozzájárulásával – statisztikai és marketing célú sütiket is használunk. ' +
        'Részletek: <a href="' + POLICY_URL + '" target="_blank" rel="noopener">adatkezelési tájékoztató</a>.</p>' +
      '</div>' +
      '<div class="cc-actions">' +
        '<button type="button" class="cc-btn cc-btn-primary" data-cc="accept-all">Elfogadom mindet</button>' +
        '<button type="button" class="cc-btn" data-cc="necessary">Csak a szükségesek</button>' +
        '<button type="button" class="cc-btn cc-btn-link" data-cc="settings">Beállítások</button>' +
      '</div>');
    document.body.appendChild(banner);
  }

  function buildPanel() {
    panel = el('div', { class: 'cc-overlay', hidden: '' },
      '<div class="cc-panel" role="dialog" aria-modal="true" aria-labelledby="ccTitle">' +
        '<h2 id="ccTitle">Cookie-beállítások</h2>' +
        '<div class="cc-cat">' +
          '<div><strong>Szükséges</strong><p>Az oldal működéséhez elengedhetetlenek, ezért nem kapcsolhatók ki.</p></div>' +
          '<label class="cc-switch"><input type="checkbox" checked disabled aria-label="Szükséges sütik (mindig be)"><span></span></label>' +
        '</div>' +
        '<div class="cc-cat">' +
          '<div><strong>Statisztikai</strong><p>Anonim látogatottsági statisztikák (pl. Google Analytics), amelyek segítenek az oldal fejlesztésében.</p></div>' +
          '<label class="cc-switch"><input type="checkbox" id="ccStat" aria-label="Statisztikai sütik"><span></span></label>' +
        '</div>' +
        '<div class="cc-cat">' +
          '<div><strong>Marketing</strong><p>Konverziómérés és releváns hirdetések megjelenítése (pl. Google Ads).</p></div>' +
          '<label class="cc-switch"><input type="checkbox" id="ccMkt" aria-label="Marketing sütik"><span></span></label>' +
        '</div>' +
        '<p class="cc-note">A hozzájárulását bármikor módosíthatja az oldal alján lévő „Cookie-beállítások” linkkel. ' +
        '<a href="' + POLICY_URL + '" target="_blank" rel="noopener">Adatkezelési tájékoztató</a></p>' +
        '<div class="cc-actions">' +
          '<button type="button" class="cc-btn cc-btn-primary" data-cc="save">Mentés</button>' +
          '<button type="button" class="cc-btn" data-cc="accept-all">Elfogadom mindet</button>' +
        '</div>' +
      '</div>');
    document.body.appendChild(panel);
  }

  function showBanner() { banner.hidden = false; }
  function hideBanner() { banner.hidden = true; }

  function openPanel() {
    var cur = load() || {};
    document.getElementById('ccStat').checked = !!cur.statistics;
    document.getElementById('ccMkt').checked  = !!cur.marketing;
    lastFocus = document.activeElement;
    panel.hidden = false;
    panel.querySelector('input:not([disabled])').focus();
  }

  function closePanel() {
    panel.hidden = true;
    if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus();
  }

  function handle(action) {
    if (action === 'accept-all')      save(true, true);
    else if (action === 'necessary')  save(false, false);
    else if (action === 'save')       save(document.getElementById('ccStat').checked, document.getElementById('ccMkt').checked);
    else if (action === 'settings')   { openPanel(); return; }
    hideBanner();
    if (!panel.hidden) closePanel();
  }

  function init() {
    buildBanner();
    buildPanel();

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cc]');
      if (btn) { handle(btn.getAttribute('data-cc')); return; }
      if (e.target.closest('[data-cookie-settings]')) { e.preventDefault(); openPanel(); }
    });

    // Esc bezár, Tab a panelen belül marad
    document.addEventListener('keydown', function (e) {
      if (panel.hidden) return;
      if (e.key === 'Escape') { closePanel(); return; }
      if (e.key !== 'Tab') return;
      var f = panel.querySelectorAll('button, input:not([disabled]), a[href]');
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    if (!load()) showBanner();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
