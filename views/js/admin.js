/**
 * Pre-Order back office: tabs, product editor, product search and live preview.
 *
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
(function () {
  'use strict';
  var app = document.getElementById('po-app');
  if (!app) { return; }
  var t = app.dataset;
  var rules = JSON.parse(t.rules || '[]');
  var $ = function (id) { return document.getElementById(id); };
  var all = function (sel, root) { return [].slice.call((root || app).querySelectorAll(sel)); };
  var radio = function (name) { return (app.querySelector('input[name="' + name + '"]:checked') || {}).value; };
  var setRadio = function (name, v) { var r = app.querySelector('input[name="' + name + '"][value="' + v + '"]'); if (r) { r.checked = true; } };
  var product = null;
  var money = function (v) {
    try { return new Intl.NumberFormat(t.locale, {style: 'currency', currency: t.iso}).format(v); } catch (e) { return v.toFixed(2) + ' ' + t.iso; }
  };
  var amountValue = function () { return parseFloat(String($('po-amount').value).replace(',', '.')); };

  /* ---------- tabs ---------- */
  function openTab(name) {
    all('.po-tab').forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.tab === name)); });
    all('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    try { sessionStorage.setItem('po_tab', name); } catch (e) { /* private window */ }
  }
  all('.po-tab').forEach(function (b, i, tabs) {
    b.addEventListener('click', function () { openTab(b.dataset.tab); });
    b.addEventListener('keydown', function (e) {
      var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (d) { var n = tabs[(i + d + tabs.length) % tabs.length]; n.focus(); openTab(n.dataset.tab); }
    });
  });
  try { if (sessionStorage.getItem('po_tab')) { openTab(sessionStorage.getItem('po_tab')); } } catch (e) { /* private window */ }

  /* ---------- editor ---------- */
  function show(editor) {
    $('po-lists').hidden = editor;
    $('po-editor').hidden = !editor;
    app.querySelector('.po-kpis').hidden = editor;
    app.querySelector('.po-head').hidden = editor;
    app.scrollIntoView({block: 'start'});
    window.scrollBy(0, -140);
  }
  function pick(p) {
    product = p;
    $('po-product').value = p ? p.id : '';
    $('po-picked').hidden = !p;
    $('po-combo').hidden = !!p;
    $('po-results').hidden = true;
    $('po-err-product').hidden = true;
    if (p) {
      $('po-picked-img').src = p.image || '';
      $('po-picked-img').hidden = !p.image;
      $('po-picked-name').textContent = p.name;
      $('po-picked-price').textContent = money(p.price);
    } else {
      $('po-search').value = '';
    }
    preview();
  }
  function open(rule) {
    $('po-editor-title').textContent = rule ? t.tEdit : t.tNew;
    $('po-editor').reset();
    setDate('');
    $('po-cal').hidden = true;
    $('po-cal-toggle').setAttribute('aria-expanded', 'false');
    pick(rule || null);
    $('po-change').hidden = !!rule; // a rule belongs to its product
    if (rule) {
      setRadio('po_mode', rule.mode);
      setRadio('po_amount_type', rule.amount_type);
      $('po-amount').value = rule.amount || 20;
      setDate(rule.date);
      $('po-max').value = rule.max;
      var restricted = rule.groups.length || rule.countries.length;
      setRadio('po_who', restricted ? 'some' : 'all');
      all('input[name="po_groups[]"]').forEach(function (c) { c.checked = rule.groups.indexOf(c.value) >= 0; });
      all('input[name="po_countries[]"]').forEach(function (c) { c.checked = rule.countries.indexOf(c.value) >= 0; });
    }
    $('po-err-amount').hidden = true;
    sync();
    show(true);
    if (!rule) { $('po-search').focus({preventScroll: true}); }
  }
  function sync() {
    var mode = radio('po_mode');
    $('po-amount-row').hidden = mode === 'full';
    $('po-amount-label').textContent = mode === 'dynamic' ? t.tMinimum : t.tDeposit;
    $('po-fixed-help').hidden = radio('po_amount_type') !== 'fixed';
    $('po-who').hidden = radio('po_who') !== 'some';
    preview();
  }
  all('[data-new]').forEach(function (b) { b.addEventListener('click', function () { open(null); }); });
  all('[data-back]').forEach(function (b) { b.addEventListener('click', function () { show(false); }); });
  all('[data-edit]').forEach(function (b) {
    b.addEventListener('click', function () {
      open(rules.filter(function (r) { return String(r.id) === b.dataset.edit; })[0]);
    });
  });
  $('po-change').addEventListener('click', function () { pick(null); $('po-search').focus(); });
  $('po-editor').addEventListener('change', sync);
  $('po-editor').addEventListener('input', preview);
  $('po-amount').addEventListener('input', function () { $('po-err-amount').hidden = true; });
  $('po-editor').addEventListener('submit', function (e) {
    var ok = true;
    if (!product) {
      $('po-err-product').textContent = t.tPick;
      $('po-err-product').hidden = false;
      $('po-search').focus();
      ok = false;
    }
    var a = amountValue();
    if (radio('po_mode') !== 'full' && (!(a > 0) || (radio('po_amount_type') === 'percent' && a > 100))) {
      $('po-err-amount').textContent = t.tBad;
      $('po-err-amount').hidden = false;
      if (ok) { $('po-amount').focus(); }
      ok = false;
    } else {
      $('po-err-amount').hidden = true;
    }
    if (!ok) { e.preventDefault(); return; }
    if (radio('po_who') !== 'some') {
      all('input[name="po_groups[]"], input[name="po_countries[]"]').forEach(function (c) { c.checked = false; });
    }
  });
  $('po-country-filter').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    all('label', $('po-country-list')).forEach(function (l) { l.hidden = l.textContent.toLowerCase().indexOf(q) < 0; });
  });

  /* ---------- product search ---------- */
  var timer, active = -1, found = [];
  var input = $('po-search'), list = $('po-results');
  function renderResults() {
    list.innerHTML = '';
    if (!found.length) {
      var none = document.createElement('li');
      none.className = 'po-result-none';
      none.textContent = t.tNone;
      list.appendChild(none);
    }
    found.forEach(function (p, i) {
      var li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', String(i === active));
      li.className = i === active ? 'on' : '';
      var img = document.createElement('img');
      img.alt = ''; img.width = 36; img.height = 36;
      if (p.image) { img.src = p.image; }
      var name = document.createElement('span');
      name.textContent = p.name;
      var price = document.createElement('small');
      price.textContent = money(p.price);
      li.appendChild(img); li.appendChild(name); li.appendChild(price);
      li.addEventListener('mousedown', function (e) { e.preventDefault(); pick(p); });
      list.appendChild(li);
    });
    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }
  function closeResults() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }
  input.addEventListener('input', function () {
    clearTimeout(timer);
    var q = input.value.trim();
    if (q.length < 2) { closeResults(); return; }
    timer = setTimeout(function () {
      fetch(t.base + '&po_search=' + encodeURIComponent(q), {credentials: 'same-origin'})
        .then(function (r) { return r.json(); })
        .then(function (d) { found = d; active = d.length ? 0 : -1; renderResults(); })
        .catch(closeResults);
    }, 220);
  });
  input.addEventListener('keydown', function (e) {
    if (list.hidden) { if (e.key === 'Enter') { e.preventDefault(); } return; }
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (found.length) { active = (active + (e.key === 'ArrowDown' ? 1 : -1) + found.length) % found.length; renderResults(); }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (found[active]) { pick(found[active]); }
    } else if (e.key === 'Escape') {
      closeResults();
    }
  });
  input.addEventListener('blur', function () { setTimeout(closeResults, 120); });

  /* ---------- live preview ---------- */
  function preview() {
    var price = product ? product.price : 100;
    // Before a product is chosen, the wording is previewed on an example price of 100.
    app.querySelector('.po-pv-prod').hidden = !product;
    if (product) {
      $('po-pv-name').textContent = product.name;
      $('po-pv-price').textContent = money(price);
    }
    var img = $('po-pv-img');
    img.hidden = !(product && product.image);
    if (product && product.image) { img.src = product.image; }
    var mode = radio('po_mode');
    var a = amountValue() || 0;
    var min = radio('po_amount_type') === 'percent' ? price * a / 100 : a;
    min = Math.min(price, Math.max(0, min));
    var text = mode === 'full'
      ? t.pFull.replace('%amount%', money(price))
      : mode === 'partial'
        ? t.pPartial.replace('%amount%', money(min)).replace('%later%', money(price - min))
        : t.pDynamic.replace('%amount%', money(min));
    $('po-pv-text').textContent = text;
    tick();
  }
  /** The date is typed in the shop's time zone, whatever zone this browser is in. */
  function shopTime(v) {
    var m = /^(\d+)-(\d+)-(\d+)T(\d+):(\d+)/.exec(v);
    return m ? Date.UTC(+m[1], m[2] - 1, +m[3], +m[4], +m[5]) - (+t.tz || 0) * 1000 : 0;
  }
  function tick() {
    var ts = shopTime($('po-date').value);
    var future = ts > Date.now();
    $('po-pv-date').hidden = !future;
    $('po-pv-count').hidden = !future;
    if (!future) { return; }
    $('po-pv-date').textContent = t.pDate.replace('%date%', fmt({dateStyle: 'medium'}).format(wall($('po-date').value)));
    var left = (ts - Date.now()) / 1000;
    var u = {d: Math.floor(left / 86400), h: Math.floor(left % 86400 / 3600), m: Math.floor(left % 3600 / 60), s: Math.floor(left % 60)};
    Object.keys(u).forEach(function (k) { $('po-pv-count').querySelector('[data-u="' + k + '"]').textContent = u[k]; });
  }

  /* ---------- date picker: quick choices, a simple calendar, one optional time ---------- */
  var DEFAULT_TIME = '09:00';
  var calMonth = null;
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var fmt = function (o) { return new Intl.DateTimeFormat(t.locale, Object.assign({timeZone: 'UTC'}, o)); };
  var wall = function (v) { var m = /^(\d+)-(\d+)-(\d+)/.exec(v); return new Date(Date.UTC(+m[1], m[2] - 1, +m[3])); };
  var key = function (d) { return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); };
  function shopToday() { var d = new Date(Date.now() + (+t.tz || 0) * 1000); return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())); }
  function addDays(n) { return new Date(shopToday().getTime() + n * 86400000); }
  function weekStart() {
    try { var w = new Intl.Locale(t.locale); var i = (w.getWeekInfo ? w.getWeekInfo() : w.weekInfo); if (i) { return i.firstDay % 7; } } catch (e) { /* older browser */ }
    return /^en-US/.test(t.locale) ? 0 : 1;
  }
  function currentTime() { return $('po-date').value.split('T')[1] || DEFAULT_TIME; }
  function setDate(v) {
    $('po-date').value = v;
    var day = v.split('T')[0];
    all('.po-chip[data-days]').forEach(function (c) {
      var on = c.dataset.days === '' ? !v : key(addDays(+c.dataset.days)) === day;
      c.classList.toggle('is-on', on);
      c.setAttribute('aria-pressed', String(on));
    });
    $('po-date-summary').hidden = !v;
    if (v) {
      var time = currentTime();
      var sel = $('po-time');
      if (!sel.querySelector('option[value="' + time + '"]')) {
        var extra = document.createElement('option');
        extra.value = extra.textContent = time;
        sel.appendChild(extra);
      }
      sel.value = time;
      var ts = shopTime(v);
      var days = Math.round((wall(v).getTime() - shopToday().getTime()) / 86400000);
      var rel = '';
      try { rel = new Intl.RelativeTimeFormat(t.locale, {numeric: 'auto'}).format(days, 'day'); } catch (e) { /* older browser */ }
      $('po-date-text').textContent = fmt({dateStyle: 'full'}).format(wall(v)) + (ts > Date.now() ? (rel ? ' · ' + rel : '') : ' · ' + t.tEnded);
      calMonth = new Date(Date.UTC(wall(v).getUTCFullYear(), wall(v).getUTCMonth(), 1));
    }
    if (!$('po-cal').hidden) { drawCalendar(); }
    preview();
  }
  function drawCalendar() {
    if (!calMonth) { var n = shopToday(); calMonth = new Date(Date.UTC(n.getUTCFullYear(), n.getUTCMonth(), 1)); }
    $('po-cal-title').textContent = fmt({month: 'long', year: 'numeric'}).format(calMonth);
    var grid = $('po-cal-grid');
    grid.innerHTML = '';
    var first = weekStart();
    for (var w = 0; w < 7; w++) {
      var head = document.createElement('span');
      head.className = 'po-cal-dow';
      head.textContent = fmt({weekday: 'short'}).format(new Date(Date.UTC(2023, 0, 1 + first + w)));
      grid.appendChild(head);
    }
    var y = calMonth.getUTCFullYear(), m = calMonth.getUTCMonth();
    var blanks = (new Date(Date.UTC(y, m, 1)).getUTCDay() - first + 7) % 7;
    for (var b = 0; b < blanks; b++) { grid.appendChild(document.createElement('span')); }
    var today = key(shopToday());
    var chosen = $('po-date').value.split('T')[0];
    var count = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    for (var d = 1; d <= count; d++) {
      (function (d) {
        var date = new Date(Date.UTC(y, m, d));
        var k = key(date);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = d;
        btn.className = 'po-cal-day' + (k === chosen ? ' is-on' : '') + (k === today ? ' is-today' : '');
        btn.setAttribute('aria-label', fmt({dateStyle: 'full'}).format(date));
        btn.setAttribute('aria-pressed', String(k === chosen));
        btn.disabled = k < today;
        btn.addEventListener('click', function () { setDate(k + 'T' + currentTime()); });
        grid.appendChild(btn);
      })(d);
    }
    var now = shopToday();
    $('po-cal-prev').disabled = y === now.getUTCFullYear() && m === now.getUTCMonth();
  }
  for (var h = 0; h < 24; h++) {
    [':00', ':30'].forEach(function (mm) {
      var o = document.createElement('option');
      o.value = o.textContent = pad(h) + mm;
      $('po-time').appendChild(o);
    });
  }
  all('.po-chip[data-rel]').forEach(function (c) {
    var r = c.dataset.rel.split(',');
    var text = c.dataset.days + ' d';
    try { text = new Intl.RelativeTimeFormat(t.locale, {numeric: 'always'}).format(+r[0], r[1]); } catch (e) { /* older browser */ }
    c.textContent = text.charAt(0).toUpperCase() + text.slice(1);
  });
  all('.po-chip[data-days]').forEach(function (c) {
    c.addEventListener('click', function () {
      setDate(c.dataset.days === '' ? '' : key(addDays(+c.dataset.days)) + 'T' + currentTime());
    });
  });
  $('po-cal-toggle').addEventListener('click', function () {
    var cal = $('po-cal');
    cal.hidden = !cal.hidden;
    this.setAttribute('aria-expanded', String(!cal.hidden));
    if (!cal.hidden) { drawCalendar(); }
  });
  ['prev', 'next'].forEach(function (dir) {
    $('po-cal-' + dir).addEventListener('click', function () {
      calMonth = new Date(Date.UTC(calMonth.getUTCFullYear(), calMonth.getUTCMonth() + (dir === 'next' ? 1 : -1), 1));
      drawCalendar();
    });
  });
  $('po-time').addEventListener('change', function () {
    setDate($('po-date').value.split('T')[0] + 'T' + this.value);
  });
  setInterval(function () { if (!$('po-editor').hidden) { tick(); } }, 1000);

  /* ---------- row actions: real POSTs, confirmed first ---------- */
  function act(field, id) {
    var f = $('po-action');
    var i = document.createElement('input');
    i.type = 'hidden'; i.name = field; i.value = id;
    f.appendChild(i);
    f.submit();
  }
  all('[data-remove]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (window.confirm(t.tRemove.replace('%product%', b.dataset.name))) { act('po_delete', b.dataset.remove); }
    });
  });
  all('[data-notify]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (window.confirm(t.tNotify.replace('%count%', b.dataset.waiting).replace('%product%', b.dataset.name))) { act('po_notify', b.dataset.notify); }
    });
  });
})();
