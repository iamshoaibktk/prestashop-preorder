<?php
/**
 * Remembers the amount a visitor chose for a pay-what-you-want pre-order.
 *
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class PreorderAmountModuleFrontController extends ModuleFrontController
{
    public function postProcess(): void
    {
        $id = (int) Tools::getValue('id_product');
        $this->context->cookie->__set('preorder_amount_' . $id, (string) max(0, (float) Tools::getValue('amount')));
        $this->context->cookie->write();
        if ($this->context->cart && $this->context->cart->id) {
            $this->module->syncCart($this->context->cart);
        }
        header('Content-Type: application/json');
        exit(json_encode(['ok' => true]));
    }
}
