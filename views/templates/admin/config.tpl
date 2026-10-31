{**
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div class="panel"><div class="panel-heading">{l s='Add or update a pre-order product' d='Modules.Preorder.Admin'}</div>
<p><a href="{$po_docs|escape:'html':'UTF-8'}" target="_blank">{l s='User guide (PDF)' d='Modules.Preorder.Admin'}</a></p>
<form method="post" action="{$po_base|escape:'html':'UTF-8'}" id="po-form">
  <div class="form-group"><label>{l s='Product' d='Modules.Preorder.Admin'}</label>
    <input type="text" id="po-search" class="form-control" autocomplete="off" placeholder="{l s='Type a product name' d='Modules.Preorder.Admin'}">
    <input type="hidden" name="po_product" id="po-product"><div id="po-results" class="list-group"></div></div>
  <div class="form-group"><label>{l s='Payment' d='Modules.Preorder.Admin'}</label>
    <select name="po_mode" class="form-control">
      <option value="full">{l s='Full payment' d='Modules.Preorder.Admin'}</option>
      <option value="partial">{l s='Deposit' d='Modules.Preorder.Admin'}</option>
      <option value="dynamic">{l s='Customer chooses the amount, above a minimum' d='Modules.Preorder.Admin'}</option>
    </select></div>
  <div class="form-group"><label>{l s='Deposit or minimum' d='Modules.Preorder.Admin'}</label>
    <input type="text" name="po_amount" class="form-control" value="20">
    <select name="po_amount_type" class="form-control"><option value="percent">%</option><option value="fixed">{l s='Fixed amount, tax included' d='Modules.Preorder.Admin'}</option></select></div>
  <div class="form-group"><label>{l s='Available from (countdown, optional)' d='Modules.Preorder.Admin'}</label><input type="datetime-local" name="po_date" class="form-control"></div>
  <div class="form-group"><label>{l s='Maximum units to pre-order (0 = no limit)' d='Modules.Preorder.Admin'}</label><input type="number" min="0" name="po_max" class="form-control" value="0"></div>
  <div class="form-group"><label>{l s='Only these customer groups (none selected = everyone)' d='Modules.Preorder.Admin'}</label><select multiple size="4" name="po_groups[]" class="form-control">{foreach $po_groups as $g}<option value="{$g.id_group|intval}">{$g.name|escape:'html':'UTF-8'}</option>{/foreach}</select></div>
  <div class="form-group"><label>{l s='Only these countries (none selected = everywhere)' d='Modules.Preorder.Admin'}</label><select multiple size="4" name="po_countries[]" class="form-control">{foreach $po_countries as $c}<option value="{$c.id_country|intval}">{$c.name|escape:'html':'UTF-8'}</option>{/foreach}</select></div>
  <button class="btn btn-primary" name="po_save" value="1">{l s='Save' d='Modules.Preorder.Admin'}</button>
</form></div>

<div class="panel"><div class="panel-heading">{l s='Pre-order products' d='Modules.Preorder.Admin'}</div>
<table class="table"><thead><tr><th>ID</th><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th>{l s='Payment' d='Modules.Preorder.Admin'}</th><th>{l s='Stock' d='Modules.Preorder.Admin'}</th><th>{l s='Reserved' d='Modules.Preorder.Admin'}</th><th>{l s='Available from' d='Modules.Preorder.Admin'}</th><th></th></tr></thead><tbody>
{foreach $po_rules as $r}<tr><td>{$r.id_product|intval}</td><td>{$r.name|escape:'html':'UTF-8'}</td><td>{$r.mode|escape:'html':'UTF-8'}{if $r.mode != 'full'} ({$r.amount|floatval}{if $r.amount_type == 'percent'}%{/if}){/if}</td><td>{$r.quantity|intval}</td><td>{$r.reserved|intval}</td><td>{$r.available_date|escape:'html':'UTF-8'}</td>
<td class="text-right"><a class="btn btn-default" href="{$po_base|escape:'html':'UTF-8'}&po_notify={$r.id_product|intval}">{l s='Notify customers now' d='Modules.Preorder.Admin'}</a> <a class="btn btn-default" href="{$po_base|escape:'html':'UTF-8'}&po_delete={$r.id_product|intval}">{l s='Remove' d='Modules.Preorder.Admin'}</a></td></tr>{foreachelse}<tr><td colspan="7">{l s='No pre-order products yet.' d='Modules.Preorder.Admin'}</td></tr>{/foreach}
</tbody></table></div>

<div class="panel"><div class="panel-heading">{l s='Product demand' d='Modules.Preorder.Admin'}</div>
<table class="table"><thead><tr><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th>{l s='Customers' d='Modules.Preorder.Admin'}</th><th>{l s='Units' d='Modules.Preorder.Admin'}</th><th>{l s='Value (tax excl.)' d='Modules.Preorder.Admin'}</th><th>{l s='Collected (tax excl.)' d='Modules.Preorder.Admin'}</th></tr></thead><tbody>
{foreach $po_demand as $d}<tr><td>{$d.name|escape:'html':'UTF-8'}</td><td>{$d.customers|intval}</td><td>{$d.units|intval}</td><td>{$d.value|string_format:"%.2f"}</td><td>{$d.collected|string_format:"%.2f"}</td></tr>{/foreach}
</tbody></table></div>

<div class="panel"><div class="panel-heading">{l s='Pre-orders' d='Modules.Preorder.Admin'}</div>
<table class="table"><thead><tr><th>{l s='Order' d='Modules.Preorder.Admin'}</th><th>{l s='Product' d='Modules.Preorder.Admin'}</th><th>{l s='Units' d='Modules.Preorder.Admin'}</th><th>{l s='Status' d='Modules.Preorder.Admin'}</th><th>{l s='Balance due (tax excl.)' d='Modules.Preorder.Admin'}</th></tr></thead><tbody>
{foreach $po_orders as $o}<tr><td>{$o.reference|escape:'html':'UTF-8'}</td><td>{$o.product|escape:'html':'UTF-8'}</td><td>{$o.qty|intval}</td><td>{$o.status_label|escape:'html':'UTF-8'}</td><td>{$o.balance|escape:'html':'UTF-8'}</td></tr>{/foreach}
</tbody></table></div>
<script>
(function () {
  var s = document.getElementById('po-search'), r = document.getElementById('po-results'), t;
  s.addEventListener('input', function () {
    clearTimeout(t);
    t = setTimeout(function () {
      fetch('{$po_base|escape:'javascript':'UTF-8'}&po_search=' + encodeURIComponent(s.value), {literal}{credentials: 'same-origin'}{/literal}).then(function (x) { return x.json(); }).then(function (list) {
        r.innerHTML = '';
        list.forEach(function (p) {
          var a = document.createElement('a');
          a.href = '#'; a.className = 'list-group-item'; a.textContent = '#' + p.id + ' ' + p.name;
          a.onclick = function (e) { e.preventDefault(); document.getElementById('po-product').value = p.id; s.value = p.name; r.innerHTML = ''; };
          r.appendChild(a);
        });
      });
    }, 250);
  });
})();
</script>
