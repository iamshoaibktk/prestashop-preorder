{**
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div class="po-box" data-po="1" data-label="{l s='Pre-order now' d='Modules.Preorder.Shop'}" data-refused="{if $po_refusal}1{else}0{/if}" data-ts="{$po_ts|intval}" data-url="{$po_url|escape:'html':'UTF-8'}" data-id="{$po_id|intval}">
  <div class="po-title">{l s='Pre-order' d='Modules.Preorder.Shop'}</div>
  {if $po_refusal}
    <p class="po-refused">{$po_refusal|escape:'html':'UTF-8'}</p>
  {else}
    {if $po_ts}
      <p class="po-date">{l s='Available on %date%' sprintf=['%date%' => $po_date] d='Modules.Preorder.Shop'}</p>
      <div class="po-count" aria-label="{l s='Time left' d='Modules.Preorder.Shop'}">
        <span><b data-u="d">0</b>{l s='days' d='Modules.Preorder.Shop'}</span><span><b data-u="h">0</b>{l s='hours' d='Modules.Preorder.Shop'}</span><span><b data-u="m">0</b>{l s='min' d='Modules.Preorder.Shop'}</span><span><b data-u="s">0</b>{l s='sec' d='Modules.Preorder.Shop'}</span>
      </div>
    {/if}
    {if $po_mode == 'full'}
      <p>{l s='Pay %amount% now. We ship as soon as it arrives.' sprintf=['%amount%' => $po_full] d='Modules.Preorder.Shop'}</p>
    {elseif $po_mode == 'partial'}
      <p>{l s='Pay %amount% now and %later% when it arrives.' sprintf=['%amount%' => $po_min_txt, '%later%' => $po_later] d='Modules.Preorder.Shop'}</p>
    {else}
      <label class="po-amount">{l s='Choose how much to pay now (minimum %amount%, per unit)' sprintf=['%amount%' => $po_min_txt] d='Modules.Preorder.Shop'}
        <input type="number" class="form-control" step="0.01" min="{$po_min|floatval}" max="{$po_max|floatval}" value="{$po_chosen|floatval}">
      </label>
      <p>{l s='The rest is due when it arrives. We will email you a payment link.' d='Modules.Preorder.Shop'}</p>
    {/if}
  {/if}
</div>
