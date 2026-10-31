<?php
/**
 * The payment link from the "your pre-order has arrived" email.
 *
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class PreorderBalanceModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();
        $token = (string) Tools::getValue('token');
        $row = preg_match('/^[a-f0-9]{32}$/', $token)
            ? Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . "preorder_order WHERE token = '" . pSQL($token) . "'")
            : false;
        $customer = $row ? new Customer((int) $row['id_customer']) : null;
        if (!$row || !$customer->id || !in_array($row['status'], ['notified', 'waiting'], true)) {
            $this->context->smarty->assign('po_message', $this->module->getTranslator()->trans('This payment link is not valid, or the balance is already paid.', [], Preorder::S));
            $this->setTemplate('module:preorder/views/templates/front/balance.tpl');

            return;
        }
        if ($customer->is_guest) {
            $this->context->updateCustomer($customer);
        } elseif ((int) $this->context->customer->id !== (int) $customer->id) {
            Tools::redirect($this->context->link->getPageLink('authentication', null, null, ['back' => $this->context->link->getModuleLink('preorder', 'balance', ['token' => $token])]));
        }
        // An unfinished balance cart is reused rather than duplicated.
        $idCart = (int) $row['id_balance_cart'];
        if ($idCart && !Order::getIdByCartId($idCart) && Db::getInstance()->getValue('SELECT 1 FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart = ' . $idCart)) {
            $this->context->cookie->id_cart = (int) $row['id_balance_cart'];
            $this->context->cart = new Cart((int) $row['id_balance_cart']);
        } else {
            $this->module->balanceCart($row);
        }
        $this->context->cookie->write();
        Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
    }
}
