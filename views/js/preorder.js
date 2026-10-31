/**
 * Pre-order box: button label, countdown, chosen amount. Survives combination changes.
 *
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
(function () {
  'use strict';
  var original = null;
  function apply() {
    var box = document.querySelector('.po-box');
    var btn = document.querySelector('#add-to-cart-or-refresh [data-button-action="add-to-cart"], .product__add-to-cart-button');
    if (!btn) { return; }
    if (original === null) { original = btn.innerHTML; }
    if (box) {
      btn.textContent = box.dataset.label;
      if (box.dataset.refused === '1') { btn.disabled = true; }
    } else {
      btn.innerHTML = original;
    }
  }
  function tick() {
    var box = document.querySelector('.po-box');
    if (!box || !+box.dataset.ts) { return; }
    var left = Math.max(0, +box.dataset.ts - Date.now() / 1000);
    var v = {d: Math.floor(left / 86400), h: Math.floor(left % 86400 / 3600), m: Math.floor(left % 3600 / 60), s: Math.floor(left % 60)};
    Object.keys(v).forEach(function (u) {
      var el = box.querySelector('[data-u="' + u + '"]');
      if (el) { el.textContent = v[u]; }
    });
  }
  document.addEventListener('change', function (e) {
    if (!e.target.matches('.po-amount input')) { return; }
    var box = e.target.closest('.po-box');
    var body = new FormData();
    body.append('id_product', box.dataset.id);
    body.append('amount', e.target.value);
    fetch(box.dataset.url, {method: 'POST', body: body, credentials: 'same-origin'});
  });
  function start() {
    apply();
    tick();
    setInterval(tick, 1000);
    if (window.prestashop && prestashop.on) { prestashop.on('updatedProduct', function () { setTimeout(function () { apply(); tick(); }, 50); }); }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
