{**
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div class="card mt-2"><div class="card-header">{l s='Pre-order' d='Modules.Preorder.Admin'}</div><div class="card-body">
{foreach $po_rows as $r}<p>{$r.product|escape:'html':'UTF-8'} × {$r.qty|intval} — <strong>{$r.status_label|escape:'html':'UTF-8'}</strong>{if $r.status == 'waiting' || $r.status == 'notified'} · {l s='Balance due (tax excl.)' d='Modules.Preorder.Admin'}: {$r.balance|escape:'html':'UTF-8'}{/if}</p>{/foreach}
</div></div>
