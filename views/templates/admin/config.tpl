{**
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<link rel="stylesheet" href="{$po_front_css|escape:'html':'UTF-8'}">
<link rel="stylesheet" href="{$po_css|escape:'html':'UTF-8'}">
<div id="po-app"
  data-base="{$po_base|escape:'html':'UTF-8'}" data-iso="{$po_iso|escape:'html':'UTF-8'}" data-locale="{$po_locale|escape:'html':'UTF-8'}" data-tz="{$po_tz|intval}"
  data-rules="{$po_rules_json|escape:'html':'UTF-8'}"
  data-t-new="{l s='New pre-order product' d='Modules.Preorder.Admin'}"
  data-t-edit="{l s='Edit pre-order product' d='Modules.Preorder.Admin'}"
  data-t-deposit="{l s='Deposit amount' d='Modules.Preorder.Admin'}"
  data-t-minimum="{l s='Minimum amount' d='Modules.Preorder.Admin'}"
  data-t-none="{l s='No products found' d='Modules.Preorder.Admin'}"
  data-t-pick="{l s='Pick a product from the list.' d='Modules.Preorder.Admin'}"
  data-t-bad="{l s='Enter a deposit above zero (at most 100 for a percentage).' d='Modules.Preorder.Admin'}"
  data-t-remove="{l s='Take %product% off pre-order? Existing pre-orders are kept.' d='Modules.Preorder.Admin'}"
  data-t-notify="{l s='Email the %count% waiting customers of %product% now?' d='Modules.Preorder.Admin'}"
  data-p-full="{l s='Pay %amount% now. We ship as soon as it arrives.' d='Modules.Preorder.Shop'}"
  data-p-partial="{l s='Pay %amount% now and %later% when it arrives.' d='Modules.Preorder.Shop'}"
  data-p-dynamic="{l s='Choose how much to pay now (minimum %amount%, per unit)' d='Modules.Preorder.Shop'}"
  data-t-ended="{l s='The countdown has ended.' d='Modules.Preorder.Admin'}"
  data-p-date="{l s='Available on %date%' d='Modules.Preorder.Shop'}">

  {$po_flash nofilter}

  <header class="po-head">
    <img src="{$po_logo|escape:'html':'UTF-8'}" alt="" width="52" height="52">
    <div class="po-head-text">
      <h2>{l s='Pre-Order' d='Modules.Preorder.Admin'}</h2>
      <p>{l s='Keep selling products that are out of stock or not released yet.' d='Modules.Preorder.Admin'}</p>
    </div>
    <a class="po-btn po-btn-ghost" href="{$po_docs|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="material-icons">menu_book</i>{l s='User guide (PDF)' d='Modules.Preorder.Admin'}</a>
  </header>

  <section class="po-kpis" aria-label="{l s='Overview' d='Modules.Preorder.Admin'}">
    <div class="po-kpi"><span class="po-kpi-label">{l s='Units reserved' d='Modules.Preorder.Admin'}</span><strong>{$po_units|intval}</strong></div>
    <div class="po-kpi"><span class="po-kpi-label">{l s='Order value' d='Modules.Preorder.Admin'}</span><strong>{$po_value|escape:'html':'UTF-8'}</strong></div>
    <div class="po-kpi"><span class="po-kpi-label">{l s='Collected' d='Modules.Preorder.Admin'}</span><strong>{$po_collected|escape:'html':'UTF-8'}</strong></div>
    <div class="po-kpi po-kpi-accent"><span class="po-kpi-label">{l s='Balance to collect' d='Modules.Preorder.Admin'}</span><strong>{$po_due|escape:'html':'UTF-8'}</strong></div>
    <p class="po-kpi-note">{l s='Amounts exclude tax and shipping.' d='Modules.Preorder.Admin'}</p>
  </section>

  {* ---------- lists ---------- *}
  <div id="po-lists">
    <div class="po-tabs" role="tablist">
      <button type="button" role="tab" class="po-tab" data-tab="products" aria-selected="true">{l s='Products' d='Modules.Preorder.Admin'}<span class="po-tab-count">{$po_rules|count}</span></button>
      <button type="button" role="tab" class="po-tab" data-tab="orders" aria-selected="false">{l s='Pre-orders' d='Modules.Preorder.Admin'}<span class="po-tab-count">{$po_orders|count}</span></button>
      <button type="button" role="tab" class="po-tab" data-tab="demand" aria-selected="false">{l s='Demand' d='Modules.Preorder.Admin'}</button>
      <button type="button" role="tab" class="po-tab" data-tab="design" aria-selected="false">{l s='Design' d='Modules.Preorder.Admin'}</button>
      <button type="button" class="po-btn po-btn-primary po-add" data-new><i class="material-icons">add</i>{l s='Add product' d='Modules.Preorder.Admin'}</button>
    </div>

    <div class="po-card" data-panel="products" role="tabpanel">
      {if $po_rules}
      <div class="po-scroll"><table class="po-table">
        <thead><tr><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th>{l s='Status' d='Modules.Preorder.Admin'}</th><th>{l s='Payment' d='Modules.Preorder.Admin'}</th><th>{l s='Release date' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Reserved' d='Modules.Preorder.Admin'}</th><th><span class="po-sr">{l s='Actions' d='Modules.Preorder.Admin'}</span></th></tr></thead>
        <tbody>
        {foreach $po_rules as $r}
          <tr>
            <td><div class="po-prod">{if $r.image}<img src="{$r.image|escape:'html':'UTF-8'}" alt="" width="44" height="44">{else}<span class="po-noimg"></span>{/if}<div><strong>{$r.name|escape:'html':'UTF-8'}</strong><small>#{$r.id|intval}</small></div></div></td>
            <td>
              {if $r.state == 'on'}<span class="po-pill po-pill-active">{l s='On pre-order' d='Modules.Preorder.Admin'}</span>
              {elseif $r.state == 'partly'}<span class="po-pill po-pill-action">{l s='Some combinations' d='Modules.Preorder.Admin'}</span>
              {else}<span class="po-pill po-pill-off">{l s='In stock, sold normally' d='Modules.Preorder.Admin'}</span>{/if}
            </td>
            <td>{$r.mode_label|escape:'html':'UTF-8'}</td>
            <td>{if $r.date_label}{$r.date_label|escape:'html':'UTF-8'}{else}<span class="po-muted">—</span>{/if}</td>
            <td class="po-num"><strong>{$r.reserved|intval}</strong>{if $r.max > 0}<small> / {$r.max|intval}</small>{/if}</td>
            <td class="po-actions">
              <button type="button" class="po-btn po-btn-secondary po-btn-sm" data-edit="{$r.id|intval}">{l s='Edit' d='Modules.Preorder.Admin'}</button>
              <button type="button" class="po-btn po-btn-secondary po-btn-sm" data-notify="{$r.id|intval}" data-name="{$r.name|escape:'html':'UTF-8'}" data-waiting="{$r.waiting|intval}" {if !$r.waiting}disabled title="{l s='No customers are waiting' d='Modules.Preorder.Admin'}"{/if}>{l s='Notify' d='Modules.Preorder.Admin'}{if $r.waiting} ({$r.waiting|intval}){/if}</button>
              <button type="button" class="po-icon-btn" data-remove="{$r.id|intval}" data-name="{$r.name|escape:'html':'UTF-8'}" aria-label="{l s='Remove' d='Modules.Preorder.Admin'}" title="{l s='Remove' d='Modules.Preorder.Admin'}"><i class="material-icons">delete_outline</i></button>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table></div>
      {else}
      <div class="po-empty"><i class="material-icons">event_available</i><h3>{l s='No pre-order products yet.' d='Modules.Preorder.Admin'}</h3><p>{l s='Add a product to start taking orders on its out-of-stock page.' d='Modules.Preorder.Admin'}</p><button type="button" class="po-btn po-btn-primary" data-new><i class="material-icons">add</i>{l s='Add product' d='Modules.Preorder.Admin'}</button></div>
      {/if}
    </div>

    <div class="po-card" data-panel="orders" role="tabpanel" hidden>
      {if $po_orders}
      <div class="po-scroll"><table class="po-table">
        <thead><tr><th>{l s='Order' d='Modules.Preorder.Admin'}</th><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Units' d='Modules.Preorder.Admin'}</th><th>{l s='Status' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Balance due' d='Modules.Preorder.Admin'}</th></tr></thead>
        <tbody>
        {foreach $po_orders as $o}
          <tr><td><a class="po-link" href="{$o.order_url|escape:'html':'UTF-8'}">{$o.reference|escape:'html':'UTF-8'}</a><small class="po-block">{$o.date|escape:'html':'UTF-8'}</small></td><td>{$o.product|escape:'html':'UTF-8'}</td><td class="po-num">{$o.qty|intval}</td><td><span class="po-pill po-pill-{$o.tone|escape:'html':'UTF-8'}">{$o.status_label|escape:'html':'UTF-8'}</span></td><td class="po-num">{$o.balance|escape:'html':'UTF-8'}</td></tr>
        {/foreach}
        </tbody>
      </table></div>
      {else}
      <div class="po-empty"><i class="material-icons">receipt_long</i><h3>{l s='No pre-orders yet.' d='Modules.Preorder.Admin'}</h3><p>{l s='They appear here as soon as a customer orders a pre-order product.' d='Modules.Preorder.Admin'}</p></div>
      {/if}
    </div>

    <div class="po-card" data-panel="demand" role="tabpanel" hidden>
      {if $po_demand}
      <div class="po-scroll"><table class="po-table">
        <thead><tr><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th class="po-demand-col">{l s='Units' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Customers' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Order value' d='Modules.Preorder.Admin'}</th><th class="po-num">{l s='Collected' d='Modules.Preorder.Admin'}</th></tr></thead>
        <tbody>
        {foreach $po_demand as $d}
          <tr><td><div class="po-prod">{if $d.image}<img src="{$d.image|escape:'html':'UTF-8'}" alt="" width="44" height="44">{else}<span class="po-noimg"></span>{/if}<strong>{$d.name|escape:'html':'UTF-8'}</strong></div></td><td><div class="po-bar"><span style="width:{$d.share|intval}%"></span></div><strong class="po-bar-n">{$d.units|intval}</strong></td><td class="po-num">{$d.customers|intval}</td><td class="po-num">{$d.value|escape:'html':'UTF-8'}</td><td class="po-num">{$d.collected|escape:'html':'UTF-8'}</td></tr>
        {/foreach}
        </tbody>
      </table></div>
      {else}
      <div class="po-empty"><i class="material-icons">insights</i><h3>{l s='No demand data yet.' d='Modules.Preorder.Admin'}</h3><p>{l s='See which products customers want most once pre-orders come in.' d='Modules.Preorder.Admin'}</p></div>
      {/if}
    </div>
    <div data-panel="design" role="tabpanel" hidden>
      <p class="po-panel-intro"><strong>{l s='Product page design' d='Modules.Preorder.Admin'}</strong> {l s='Choose how the pre-order box looks in your store.' d='Modules.Preorder.Admin'}</p>
      <div class="po-gallery">
      {foreach $po_styles as $st}
        <article class="po-card po-design{if $st.id == $po_style} is-on{/if}">
          <div class="po-design-stage">
            <div class="po-box po-style-{$st.id|escape:'html':'UTF-8'}">
              <div class="po-title">{l s='Pre-order' d='Modules.Preorder.Shop'}</div>
              <p class="po-date">{l s='Available on %date%' sprintf=['%date%' => $po_sample_date] d='Modules.Preorder.Shop'}</p>
              <div class="po-count"><span><b>12</b>{l s='days' d='Modules.Preorder.Shop'}</span><span><b>5</b>{l s='hours' d='Modules.Preorder.Shop'}</span><span><b>30</b>{l s='min' d='Modules.Preorder.Shop'}</span><span><b>45</b>{l s='sec' d='Modules.Preorder.Shop'}</span></div>
              <p>{l s='Pay %amount% now and %later% when it arrives.' sprintf=['%amount%' => $po_sample_now, '%later%' => $po_sample_later] d='Modules.Preorder.Shop'}</p>
            </div>
          </div>
          <div class="po-design-foot">
            <div><strong>{$st.name|escape:'html':'UTF-8'}</strong><small>{$st.text|escape:'html':'UTF-8'}</small></div>
            {if $st.id == $po_style}
              <span class="po-pill po-pill-done"><i class="material-icons">check</i>{l s='In use' d='Modules.Preorder.Admin'}</span>
            {else}
              <form method="post" action="{$po_base|escape:'html':'UTF-8'}"><input type="hidden" name="po_style" value="{$st.id|escape:'html':'UTF-8'}"><button class="po-btn po-btn-secondary po-btn-sm">{l s='Use this design' d='Modules.Preorder.Admin'}</button></form>
            {/if}
          </div>
        </article>
      {/foreach}
      </div>
    </div>
  </div>

  {* ---------- editor ---------- *}
  <form id="po-editor" method="post" action="{$po_base|escape:'html':'UTF-8'}" hidden novalidate>
    <input type="hidden" name="po_save" value="1">
    <input type="hidden" name="po_product" id="po-product">
    <div class="po-editor-top">
      <button type="button" class="po-btn po-btn-ghost" data-back><i class="material-icons">arrow_back</i>{l s='Back to products' d='Modules.Preorder.Admin'}</button>
      <h3 id="po-editor-title"></h3>
    </div>
    <div class="po-editor-grid">
      <div class="po-editor-main">
        <section class="po-card po-section">
          <h4>{l s='Product' d='Modules.Preorder.Admin'}</h4>
          <div id="po-picked" class="po-picked" hidden><div class="po-prod"><img id="po-picked-img" alt="" width="44" height="44"><div><strong id="po-picked-name"></strong><small id="po-picked-price"></small></div></div><button type="button" class="po-btn po-btn-ghost po-btn-sm" id="po-change">{l s='Change' d='Modules.Preorder.Admin'}</button></div>
          <div id="po-combo" class="po-combo">
            <div class="po-input-icon"><i class="material-icons">search</i><input type="text" id="po-search" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="po-results" aria-label="{l s='Product' d='Modules.Preorder.Admin'}" placeholder="{l s='Type a product name' d='Modules.Preorder.Admin'}"></div>
            <ul id="po-results" class="po-results" role="listbox" hidden></ul>
          </div>
          <p class="po-error" id="po-err-product" hidden></p>
        </section>

        <section class="po-card po-section">
          <h4>{l s='What customers pay' d='Modules.Preorder.Admin'}</h4>
          <div class="po-modes" role="radiogroup">
            <label class="po-mode"><input type="radio" name="po_mode" value="full" checked><span><i class="material-icons">payments</i><strong>{l s='Full payment' d='Modules.Preorder.Admin'}</strong><small>{l s='The whole price at checkout. Best for low-priced products.' d='Modules.Preorder.Admin'}</small></span></label>
            <label class="po-mode"><input type="radio" name="po_mode" value="partial"><span><i class="material-icons">pie_chart_outline</i><strong>{l s='Deposit' d='Modules.Preorder.Admin'}</strong><small>{l s='A part now, the rest when the product arrives.' d='Modules.Preorder.Admin'}</small></span></label>
            <label class="po-mode"><input type="radio" name="po_mode" value="dynamic"><span><i class="material-icons">tune</i><strong>{l s='Customer chooses' d='Modules.Preorder.Admin'}</strong><small>{l s='Any amount above your minimum, the rest later.' d='Modules.Preorder.Admin'}</small></span></label>
          </div>
          <div id="po-amount-row" class="po-field" hidden>
            <label for="po-amount" id="po-amount-label"></label>
            <div class="po-amount">
              <input type="text" inputmode="decimal" id="po-amount" name="po_amount" value="20">
              <div class="po-seg" role="radiogroup">
                <label><input type="radio" name="po_amount_type" value="percent" checked><span>%</span></label>
                <label><input type="radio" name="po_amount_type" value="fixed"><span>{$po_iso|escape:'html':'UTF-8'}</span></label>
              </div>
            </div>
            <p class="po-help" id="po-fixed-help" hidden>{l s='Fixed amount, tax included' d='Modules.Preorder.Admin'}</p>
            <p class="po-error" id="po-err-amount" hidden></p>
          </div>
        </section>

        <section class="po-card po-section">
          <h4>{l s='Availability' d='Modules.Preorder.Admin'}</h4>
          <div class="po-field po-field-first">
            <span class="po-label" id="po-date-label">{l s='Release date' d='Modules.Preorder.Admin'} <small>{l s='Optional' d='Modules.Preorder.Admin'}</small></span>
            <input type="hidden" id="po-date" name="po_date">
            <div class="po-chips" role="group" aria-labelledby="po-date-label">
              <button type="button" class="po-chip" data-days="">{l s='No date' d='Modules.Preorder.Admin'}</button>
              <button type="button" class="po-chip" data-days="7" data-rel="1,week"></button>
              <button type="button" class="po-chip" data-days="14" data-rel="2,week"></button>
              <button type="button" class="po-chip" data-days="30" data-rel="1,month"></button>
              <button type="button" class="po-chip" data-days="90" data-rel="3,month"></button>
              <button type="button" class="po-chip" id="po-cal-toggle" aria-expanded="false"><i class="material-icons">calendar_today</i>{l s='Pick a date' d='Modules.Preorder.Admin'}</button>
            </div>
            <div id="po-cal" class="po-cal" hidden>
              <div class="po-cal-head">
                <button type="button" class="po-icon-btn" id="po-cal-prev" aria-label="{l s='Previous month' d='Modules.Preorder.Admin'}"><i class="material-icons">chevron_left</i></button>
                <strong id="po-cal-title" aria-live="polite"></strong>
                <button type="button" class="po-icon-btn" id="po-cal-next" aria-label="{l s='Next month' d='Modules.Preorder.Admin'}"><i class="material-icons">chevron_right</i></button>
              </div>
              <div class="po-cal-grid" id="po-cal-grid"></div>
            </div>
            <div id="po-date-summary" class="po-date-summary" hidden>
              <i class="material-icons">event</i><span id="po-date-text"></span>
              <label class="po-time">{l s='Time' d='Modules.Preorder.Admin'} <select id="po-time"></select></label>
            </div>
            <p class="po-help">{l s='Shows a countdown on the product page.' d='Modules.Preorder.Admin'}</p>
          </div>
          <div class="po-field po-field-limit"><label for="po-max">{l s='Unit limit' d='Modules.Preorder.Admin'} <small>{l s='Optional' d='Modules.Preorder.Admin'}</small></label><input type="number" min="0" id="po-max" name="po_max" value="0"><p class="po-help">{l s='Stops pre-orders after this many units. 0 means no limit.' d='Modules.Preorder.Admin'}</p></div>
        </section>

        <section class="po-card po-section">
          <h4>{l s='Who can pre-order' d='Modules.Preorder.Admin'}</h4>
          <div class="po-seg po-seg-lg" role="radiogroup">
            <label><input type="radio" name="po_who" value="all" checked><span>{l s='Everyone' d='Modules.Preorder.Admin'}</span></label>
            <label><input type="radio" name="po_who" value="some"><span>{l s='Only selected' d='Modules.Preorder.Admin'}</span></label>
          </div>
          <div id="po-who" class="po-two po-who" hidden>
            <fieldset><legend>{l s='Customer groups' d='Modules.Preorder.Admin'}</legend><div class="po-checks">{foreach $po_groups as $g}<label><input type="checkbox" name="po_groups[]" value="{$g.id_group|intval}"> {$g.name|escape:'html':'UTF-8'}</label>{/foreach}</div></fieldset>
            <fieldset><legend>{l s='Countries' d='Modules.Preorder.Admin'}</legend><input type="search" id="po-country-filter" placeholder="{l s='Search countries' d='Modules.Preorder.Admin'}" aria-label="{l s='Search countries' d='Modules.Preorder.Admin'}"><div class="po-checks" id="po-country-list">{foreach $po_countries as $c}<label><input type="checkbox" name="po_countries[]" value="{$c.id_country|intval}"> {$c.name|escape:'html':'UTF-8'}</label>{/foreach}</div></fieldset>
            <p class="po-help po-span">{l s='Nothing ticked in a list means no limit on it.' d='Modules.Preorder.Admin'}</p>
          </div>
        </section>
      </div>

      <aside class="po-preview-col">
        <div class="po-card po-section po-preview">
          <h4>{l s='Preview on the product page' d='Modules.Preorder.Admin'}</h4>
          <div class="po-pv-prod"><img id="po-pv-img" alt="" hidden><div><strong id="po-pv-name">—</strong><span id="po-pv-price"></span></div></div>
          <div class="po-box po-style-{$po_style|escape:'html':'UTF-8'}" id="po-pv-box">
            <div class="po-title">{l s='Pre-order' d='Modules.Preorder.Shop'}</div>
            <p class="po-date" id="po-pv-date" hidden></p>
            <div id="po-pv-count" class="po-count" hidden><span><b data-u="d">0</b>{l s='days' d='Modules.Preorder.Shop'}</span><span><b data-u="h">0</b>{l s='hours' d='Modules.Preorder.Shop'}</span><span><b data-u="m">0</b>{l s='min' d='Modules.Preorder.Shop'}</span><span><b data-u="s">0</b>{l s='sec' d='Modules.Preorder.Shop'}</span></div>
            <p id="po-pv-text"></p>
          </div>
          <div class="po-pv-btn">{l s='Pre-order now' d='Modules.Preorder.Shop'}</div>
        </div>
      </aside>
    </div>
    <div class="po-savebar">
      <button type="button" class="po-btn po-btn-ghost" data-back>{l s='Cancel' d='Modules.Preorder.Admin'}</button>
      <button type="submit" class="po-btn po-btn-primary"><i class="material-icons">check</i>{l s='Save' d='Modules.Preorder.Admin'}</button>
    </div>
  </form>

  <form id="po-action" method="post" action="{$po_base|escape:'html':'UTF-8'}" hidden></form>
</div>
<script src="{$po_js|escape:'html':'UTF-8'}"></script>
