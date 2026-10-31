<?php
/**
 * Pre-Order - Sell Out-of-Stock and Upcoming Products
 *
 * @author    iamshoaibktk@gmail.com (WhatsApp: +923459016286)
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Deposits use a price scoped to the customer's cart, so checkout, taxes and every payment module
 * work unchanged. The balance is a second cart priced at the remainder, with free shipping, whose
 * order gives its stock back (the first order already took it). No core overrides.
 */
class Preorder extends Module
{
    public const D = 'Modules.Preorder.Admin';
    public const S = 'Modules.Preorder.Shop';
    private const LANG_LOCALES = ['en' => 'en-US', 'fr' => 'fr-FR', 'es' => 'es-ES', 'it' => 'it-IT', 'pl' => 'pl-PL', 'de' => 'de-DE', 'cs' => 'cs-CZ', 'nl' => 'nl-NL', 'pt' => 'pt-PT', 'ru' => 'ru-RU'];

    /** Our own stock corrections must not count as stock arriving. */
    private static bool $silentStock = false;

    public function __construct()
    {
        $this->name = 'preorder';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'iamshoaibktk@gmail.com';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Pre-Order - Sell Out-of-Stock and Upcoming Products', [], self::D);
        $this->description = $this->trans('Sell out-of-stock and upcoming products with full payment, a deposit or a customer-chosen amount, a countdown, and automatic emails when stock arrives.', [], self::D);
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], self::D);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        $p = _DB_PREFIX_;
        $e = _MYSQL_ENGINE_;
        $sql = [
            "CREATE TABLE IF NOT EXISTS {$p}preorder_rule (
                id_product INT UNSIGNED NOT NULL PRIMARY KEY, active TINYINT(1) NOT NULL DEFAULT 1,
                mode VARCHAR(10) NOT NULL DEFAULT 'full', amount_type VARCHAR(10) NOT NULL DEFAULT 'percent',
                amount DECIMAL(20,6) NOT NULL DEFAULT 0, available_date DATETIME NULL, max_qty INT UNSIGNED NOT NULL DEFAULT 0,
                `groups` VARCHAR(255) NOT NULL DEFAULT '', countries VARCHAR(255) NOT NULL DEFAULT '', prev_oos TINYINT(1) NOT NULL DEFAULT 2
            ) ENGINE=$e DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS {$p}preorder_cart (
                id_cart INT UNSIGNED NOT NULL, id_product INT UNSIGNED NOT NULL, id_product_attribute INT UNSIGNED NOT NULL,
                unit_full DECIMAL(20,6) NOT NULL, unit_pay DECIMAL(20,6) NOT NULL, id_specific_price INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id_cart, id_product, id_product_attribute)
            ) ENGINE=$e DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS {$p}preorder_order (
                id_preorder INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, id_order INT UNSIGNED NOT NULL, id_customer INT UNSIGNED NOT NULL,
                id_product INT UNSIGNED NOT NULL, id_product_attribute INT UNSIGNED NOT NULL, qty INT UNSIGNED NOT NULL,
                unit_full DECIMAL(20,6) NOT NULL, unit_paid DECIMAL(20,6) NOT NULL, status VARCHAR(10) NOT NULL DEFAULT 'waiting',
                token VARCHAR(32) NOT NULL, id_balance_cart INT UNSIGNED NOT NULL DEFAULT 0, id_balance_order INT UNSIGNED NOT NULL DEFAULT 0,
                date_add DATETIME NOT NULL, KEY (id_product, id_product_attribute), KEY (id_balance_cart), KEY (id_order)
            ) ENGINE=$e DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($sql as $q) {
            if (!Db::getInstance()->execute($q)) {
                return false;
            }
        }

        return parent::install() && $this->registerHook([
            'displayProductAdditionalInfo', 'displayShoppingCart', 'displayAdminOrderMain', 'actionFrontControllerSetMedia',
            'actionCartControllerInitBefore', 'actionCartSave', 'actionValidateOrder', 'actionUpdateQuantity',
        ]);
    }

    public function uninstall(): bool
    {
        foreach (Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_rule') ?: [] as $r) {
            $this->restoreStockPolicy($r);
        }
        foreach (Db::getInstance()->executeS('SELECT id_specific_price FROM ' . _DB_PREFIX_ . 'preorder_cart WHERE id_specific_price > 0') ?: [] as $r) {
            Db::getInstance()->delete('specific_price', 'id_specific_price = ' . (int) $r['id_specific_price']);
        }
        foreach (['preorder_rule', 'preorder_cart', 'preorder_order'] as $t) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS ' . _DB_PREFIX_ . $t);
        }

        return parent::uninstall();
    }

    // ------------------------------------------------------------------ rules
    public function getRule(int $idProduct): ?array
    {
        $r = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_rule WHERE active = 1 AND id_product = ' . $idProduct);

        return $r ?: null;
    }

    /** A product is on pre-order while its rule is active and this combination has no stock. */
    public function selling(int $idProduct, int $ipa): ?array
    {
        $rule = $this->getRule($idProduct);

        return $rule && (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $ipa) <= 0 ? $rule : null;
    }

    /** Null when this visitor may pre-order, otherwise the reason. */
    private function refusal(array $rule): ?string
    {
        $groups = array_filter(explode(',', $rule['groups']));
        if ($groups) {
            $mine = $this->context->customer && $this->context->customer->id
                ? Customer::getGroupsStatic((int) $this->context->customer->id)
                : [(int) Configuration::get('PS_UNIDENTIFIED_GROUP'), (int) Configuration::get('PS_GUEST_GROUP')];
            if (!array_intersect($groups, $mine)) {
                return $this->trans('Pre-orders for this product are reserved for selected customers.', [], self::S);
            }
        }
        $countries = array_filter(explode(',', $rule['countries']));
        if ($countries) {
            $cart = $this->context->cart;
            $id = $cart && $cart->id_address_delivery ? (int) (new Address((int) $cart->id_address_delivery))->id_country : (int) $this->context->country->id;
            if (!in_array((string) $id, $countries, true)) {
                return $this->trans('This product cannot be pre-ordered from your country.', [], self::S);
            }
        }
        if ((int) $rule['max_qty'] > 0 && $this->reserved((int) $rule['id_product']) >= (int) $rule['max_qty']) {
            return $this->trans('All available pre-orders for this product are taken.', [], self::S);
        }

        return null;
    }

    private function reserved(int $idProduct): int
    {
        return (int) Db::getInstance()->getValue('SELECT SUM(qty) FROM ' . _DB_PREFIX_ . 'preorder_order WHERE id_product = ' . $idProduct);
    }

    /** What to pay now per unit, tax included, for a full price; dynamic mode uses the visitor's chosen amount. */
    public function payNow(array $rule, float $full): float
    {
        if ($rule['mode'] === 'full') {
            return $full;
        }
        $min = $this->minimum($rule, $full);
        if ($rule['mode'] === 'dynamic') {
            $chosen = (float) $this->context->cookie->__get('preorder_amount_' . (int) $rule['id_product']);

            return $chosen > 0 ? min($full, max($min, $chosen)) : $min;
        }

        return $min;
    }

    public function minimum(array $rule, float $full): float
    {
        $v = $rule['amount_type'] === 'percent'
            ? $full * (float) $rule['amount'] / 100
            : Tools::convertPrice((float) $rule['amount'], $this->context->currency);

        return round(min($full, max(0, $v)), 2);
    }

    private function setStockPolicy(int $idProduct, array $rule = []): void
    {
        $current = (int) StockAvailable::outOfStock($idProduct);
        if (!$rule || (int) $rule['prev_oos'] === 2) {
            Db::getInstance()->update('preorder_rule', ['prev_oos' => $current], 'id_product = ' . $idProduct);
        }
        StockAvailable::setProductOutOfStock($idProduct, 1);
    }

    private function restoreStockPolicy(array $rule): void
    {
        StockAvailable::setProductOutOfStock((int) $rule['id_product'], (int) $rule['prev_oos'] === 2 ? 2 : (int) $rule['prev_oos']);
    }

    // ------------------------------------------------------------------ cart
    private static function money(string|float $v): string
    {
        return number_format((float) $v, 6, '.', '');
    }

    /** Makes this cart line cost exactly $targetExcl per unit through a cart-only specific price. */
    public function priceLine(Cart $cart, int $idProduct, int $ipa, float $targetExcl): int
    {
        $sp = new SpecificPrice();
        $sp->id_cart = (int) $cart->id;
        $sp->id_product = $idProduct;
        $sp->id_product_attribute = $ipa;
        $sp->id_shop = (int) $cart->id_shop;
        $sp->id_shop_group = 0;
        $sp->id_currency = (int) $cart->id_currency;
        $sp->id_country = 0;
        $sp->id_group = 0;
        $sp->id_customer = 0;
        $sp->from_quantity = 1;
        $sp->price = round($targetExcl, 6);
        $sp->reduction = 0;
        $sp->reduction_tax = 1;
        $sp->reduction_type = 'amount';
        $sp->from = '0000-00-00 00:00:00';
        $sp->to = '0000-00-00 00:00:00';
        $sp->add();
        self::flushPrices();
        // Group discounts apply on top of a specific price: correct for them once.
        $actual = Product::getPriceStatic($idProduct, false, $ipa, 6, null, false, true, 1, false, null, (int) $cart->id);
        if ($actual > 0 && abs($actual - $targetExcl) > 0.0001) {
            $sp->price = round($sp->price * $targetExcl / $actual, 6);
            $sp->update();
            self::flushPrices();
        }

        return (int) $sp->id;
    }

    private static function flushPrices(): void
    {
        Product::flushPriceCache();
        SpecificPrice::flushCache();
        Cache::clear();
    }

    private function isBalanceCart(int $idCart): bool
    {
        return $idCart > 0 && (bool) Db::getInstance()->getValue('SELECT 1 FROM ' . _DB_PREFIX_ . 'preorder_order WHERE id_balance_cart = ' . $idCart);
    }

    /** Brings the deposit prices of the cart in line with what is in it. */
    public function syncCart(Cart $cart): void
    {
        if (!$cart->id || $this->isBalanceCart((int) $cart->id)) {
            return;
        }
        $db = Db::getInstance();
        $rows = $db->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_cart WHERE id_cart = ' . (int) $cart->id) ?: [];
        foreach ($rows as $r) {
            if ($r['id_specific_price']) {
                $db->delete('specific_price', 'id_specific_price = ' . (int) $r['id_specific_price']);
            }
        }
        $db->delete('preorder_cart', 'id_cart = ' . (int) $cart->id);
        self::flushPrices();

        $lines = $db->executeS('SELECT id_product, id_product_attribute FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart = ' . (int) $cart->id) ?: [];
        foreach ($lines as $l) {
            $idP = (int) $l['id_product'];
            $ipa = (int) $l['id_product_attribute'];
            $rule = $this->selling($idP, $ipa);
            if (!$rule) {
                continue;
            }
            $fullExcl = Product::getPriceStatic($idP, false, $ipa, 6, null, false, true, 1, false, null, (int) $cart->id);
            $fullIncl = Product::getPriceStatic($idP, true, $ipa, 6, null, false, true, 1, false, null, (int) $cart->id);
            $payIncl = $this->payNow($rule, $fullIncl);
            $payExcl = $fullIncl > 0 ? $fullExcl * $payIncl / $fullIncl : 0;
            $idSp = $payIncl < $fullIncl ? $this->priceLine($cart, $idP, $ipa, $payExcl) : 0;
            $db->insert('preorder_cart', [
                'id_cart' => (int) $cart->id, 'id_product' => $idP, 'id_product_attribute' => $ipa,
                'unit_full' => self::money($fullExcl), 'unit_pay' => self::money($payExcl), 'id_specific_price' => $idSp,
            ]);
        }
        self::flushPrices();
    }

    public function hookActionCartSave(array $params): void
    {
        $cart = $params['cart'] ?? $this->context->cart;
        if ($cart instanceof Cart && $this->context->controller instanceof FrontController) {
            $this->syncCart($cart);
        }
    }

    /** Stops what checkout could not honour, before it reaches the cart. */
    public function hookActionCartControllerInitBefore(): void
    {
        if (!Tools::getValue('add') && !Tools::getValue('update')) {
            return;
        }
        // This hook runs before the controller loads the cart.
        $idCart = (int) $this->context->cookie->id_cart;
        $balance = $this->isBalanceCart($idCart);
        if ($balance && !Db::getInstance()->getValue('SELECT 1 FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart = ' . $idCart)) {
            // The customer removed the balance from the cart: let them shop in a new one.
            $this->context->cookie->id_cart = 0;
            $this->context->cart = null;
            $idCart = 0;
            $balance = false;
        }
        if (!$balance && (Tools::getValue('op') === 'down' || Tools::getValue('delete'))) {
            return;
        }
        $idP = (int) Tools::getValue('id_product');
        if (!$idP) {
            return;
        }
        $ipa = (int) Tools::getValue('id_product_attribute');
        if (!$ipa && Tools::getIsset('group')) {
            $ipa = (int) Product::getIdProductAttributeByIdAttributes($idP, Tools::getValue('group'));
        }
        if (!$ipa) {
            $ipa = (int) Product::getDefaultAttribute($idP);
        }
        $error = null;
        if ($idCart && !$this->context->cart) {
            $this->context->cart = new Cart($idCart);
        }
        $rule = $this->selling($idP, $ipa);
        if ($balance) {
            // The balance price is only valid for the exact quantity that was reserved.
            $error = $this->trans('This cart is for your pre-order balance. Please pay it first, then continue shopping.', [], self::S);
        } elseif ($rule) {
            $error = $this->refusal($rule);
        }
        if (!$error && $idCart && Tools::getValue('add')) {
            $hasRegular = false;
            $hasPre = false;
            foreach (Db::getInstance()->executeS('SELECT id_product, id_product_attribute FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart = ' . $idCart) ?: [] as $l) {
                if ((int) $l['id_product'] === $idP && (int) $l['id_product_attribute'] === $ipa) {
                    continue;
                }
                $this->selling((int) $l['id_product'], (int) $l['id_product_attribute']) ? $hasPre = true : $hasRegular = true;
            }
            if (($rule && $hasRegular) || (!$rule && $hasPre)) {
                $error = $this->trans('Pre-order products and regular products must be ordered separately. Please complete or empty your cart first.', [], self::S);
            }
        }
        if ($error) {
            header('Content-Type: application/json');
            exit(json_encode(['hasError' => true, 'errors' => [$error], 'quantity' => 0]));
        }
    }

    public function hookActionValidateOrder(array $params): void
    {
        $order = $params['order'] ?? null;
        if (!$order instanceof Order) {
            return;
        }
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $idCart = (int) $order->id_cart;

        // A balance order: the deposit order already took the stock, so give this one back.
        $balances = $db->executeS("SELECT * FROM {$p}preorder_order WHERE id_balance_cart = $idCart AND id_balance_order = 0") ?: [];
        foreach ($balances as $b) {
            self::$silentStock = true;
            StockAvailable::updateQuantity((int) $b['id_product'], (int) $b['id_product_attribute'], (int) $b['qty']);
            self::$silentStock = false;
            $db->update('preorder_order', ['status' => 'paid', 'id_balance_order' => (int) $order->id], 'id_preorder = ' . (int) $b['id_preorder']);
        }

        $rows = $db->executeS("SELECT pc.*, cp.quantity FROM {$p}preorder_cart pc
            JOIN {$p}cart_product cp ON (cp.id_cart = pc.id_cart AND cp.id_product = pc.id_product AND cp.id_product_attribute = pc.id_product_attribute)
            WHERE pc.id_cart = $idCart") ?: [];
        foreach ($rows as $r) {
            $full = (float) $r['unit_pay'] >= (float) $r['unit_full'] - 0.0001;
            $db->insert('preorder_order', [
                'id_order' => (int) $order->id, 'id_customer' => (int) $order->id_customer,
                'id_product' => (int) $r['id_product'], 'id_product_attribute' => (int) $r['id_product_attribute'], 'qty' => (int) $r['quantity'],
                'unit_full' => $r['unit_full'], 'unit_paid' => $r['unit_pay'], 'status' => $full ? 'full' : 'waiting',
                'token' => bin2hex(random_bytes(16)), 'date_add' => date('Y-m-d H:i:s'),
            ]);
        }
        // The cart stays in the order's history, so its prices must not be sold again.
        foreach ($rows as $r) {
            if ($r['id_specific_price']) {
                $db->delete('specific_price', 'id_specific_price = ' . (int) $r['id_specific_price']);
            }
        }
        $db->delete('preorder_cart', 'id_cart = ' . $idCart);
    }

    // ------------------------------------------------------------------ stock arrives
    public function hookActionUpdateQuantity(array $params): void
    {
        if (self::$silentStock || (int) ($params['quantity'] ?? 0) <= 0 || (int) ($params['delta_quantity'] ?? 1) <= 0) {
            return;
        }
        $this->notifyArrival((int) $params['id_product'], (int) ($params['id_product_attribute'] ?? 0));
    }

    /** @return int customers emailed */
    public function notifyArrival(int $idProduct, int $ipa = -1): int
    {
        $where = 'id_product = ' . $idProduct . ($ipa >= 0 ? ' AND id_product_attribute = ' . $ipa : '');
        $rows = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . "preorder_order WHERE $where AND status IN ('waiting','full') ORDER BY id_preorder") ?: [];
        $sent = 0;
        foreach ($rows as $r) {
            $sent += $this->sendArrival($r) ? 1 : 0;
            Db::getInstance()->update('preorder_order', ['status' => $r['status'] === 'full' ? 'shipped' : 'notified'], 'id_preorder = ' . (int) $r['id_preorder']);
        }

        return $sent;
    }

    private function sendArrival(array $r): bool
    {
        $order = new Order((int) $r['id_order']);
        $customer = new Customer((int) $r['id_customer']);
        if (!$customer->id) {
            return false;
        }
        $lang = new Language((int) $order->id_lang);
        $locale = self::LANG_LOCALES[$lang->iso_code] ?? 'en-US';
        $name = Product::getProductName((int) $r['id_product'], (int) $r['id_product_attribute'], (int) $order->id_lang);
        $hasBalance = $r['status'] === 'waiting';
        $link = $hasBalance ? $this->context->link->getModuleLink($this->name, 'balance', ['token' => $r['token']], true, (int) $order->id_lang) : '';
        $vars = [
            '{title}' => $this->trans('Your pre-order has arrived', [], self::S, $locale),
            '{message}' => $hasBalance
                ? $this->trans('Good news: %product% is back in stock. Please pay the remaining balance to receive your order %reference%.', ['%product%' => $name, '%reference%' => $order->reference], self::S, $locale)
                : $this->trans('Good news: %product% is in stock and your order %reference% is on its way to being shipped.', ['%product%' => $name, '%reference%' => $order->reference], self::S, $locale),
            '{link}' => $link,
            '{link_html}' => $hasBalance ? '<p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;padding:12px 24px;background:#111827;color:#fff;border-radius:6px;text-decoration:none">' . htmlspecialchars($this->trans('Pay the balance', [], self::S, $locale)) . '</a></p>' : '',
        ];

        try {
            return (bool) Mail::send((int) $order->id_lang, 'preorder_arrival', $vars['{title}'], $vars, $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null, null, _PS_MODULE_DIR_ . $this->name . '/mails/', false, (int) $order->id_shop);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('Pre-Order: ' . $e->getMessage(), 2);

            return false;
        }
    }

    /** Opens a cart for the balance of a pre-order. Returns it, or null when nothing is due. */
    public function balanceCart(array $r): ?Cart
    {
        if ($r['status'] !== 'notified' && $r['status'] !== 'waiting') {
            return null;
        }
        $order = new Order((int) $r['id_order']);
        $cart = new Cart();
        $cart->id_customer = (int) $order->id_customer;
        $cart->id_address_delivery = (int) $order->id_address_delivery;
        $cart->id_address_invoice = (int) $order->id_address_invoice;
        $cart->id_currency = (int) $order->id_currency;
        $cart->id_lang = (int) $order->id_lang;
        $cart->id_shop = (int) $order->id_shop;
        $cart->id_shop_group = (int) $order->id_shop_group;
        $cart->id_carrier = (int) $order->id_carrier;
        $cart->secure_key = (string) $order->secure_key;
        $cart->add();
        Db::getInstance()->update('preorder_order', ['id_balance_cart' => (int) $cart->id], 'id_preorder = ' . (int) $r['id_preorder']);
        $this->context->cart = $cart;
        $this->context->cookie->id_cart = (int) $cart->id;
        Db::getInstance()->insert('cart_product', [
            'id_cart' => (int) $cart->id, 'id_product' => (int) $r['id_product'], 'id_product_attribute' => (int) $r['id_product_attribute'],
            'id_address_delivery' => (int) $order->id_address_delivery, 'id_shop' => (int) $order->id_shop, 'quantity' => (int) $r['qty'], 'date_add' => date('Y-m-d H:i:s'),
        ]);
        $this->priceLine($cart, (int) $r['id_product'], (int) $r['id_product_attribute'], max(0, (float) $r['unit_full'] - (float) $r['unit_paid']));

        // Shipping was already paid with the deposit.
        $rule = new CartRule();
        foreach (Language::getIDs(false) as $l) {
            $rule->name[$l] = $this->trans('Pre-order balance', [], self::S);
        }
        $rule->id_customer = (int) $order->id_customer;
        $rule->code = 'PREORDER' . (int) $r['id_preorder'] . strtoupper(Tools::passwdGen(6));
        $rule->free_shipping = 1;
        $rule->quantity = 1;
        $rule->quantity_per_user = 1;
        $rule->date_from = date('Y-m-d H:i:s', time() - 3600);
        $rule->date_to = date('Y-m-d H:i:s', time() + 86400 * 365);
        $rule->active = 1;
        $rule->add();
        $cart->addCartRule((int) $rule->id);

        return $cart;
    }

    // ------------------------------------------------------------------ storefront
    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->registerStylesheet('preorder', 'modules/' . $this->name . '/views/css/preorder.css');
            $this->context->controller->registerJavascript('preorder', 'modules/' . $this->name . '/views/js/preorder.js', ['position' => 'bottom', 'priority' => 200]);
        }
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        $product = $params['product'] ?? null;
        if (!$product) {
            return '';
        }
        $idP = (int) $product['id_product'];
        $ipa = (int) ($product['id_product_attribute'] ?? 0);
        $rule = $this->selling($idP, $ipa);
        if (!$rule) {
            return '';
        }
        $refusal = $this->refusal($rule);
        $full = Product::getPriceStatic($idP, true, $ipa);
        $locale = $this->context->getCurrentLocale();
        $iso = $this->context->currency->iso_code;
        $date = $rule['available_date'] && strtotime($rule['available_date']) > time() ? $rule['available_date'] : null;
        $this->context->smarty->assign([
            'po_mode' => $rule['mode'],
            'po_refusal' => $refusal,
            'po_ts' => $date ? strtotime($date) : 0,
            'po_date' => $date ? Tools::displayDate($date) : '',
            'po_full' => $locale->formatPrice($full, $iso),
            'po_min' => $this->minimum($rule, $full),
            'po_min_txt' => $locale->formatPrice($this->minimum($rule, $full), $iso),
            'po_later' => $locale->formatPrice(max(0, $full - $this->minimum($rule, $full)), $iso),
            'po_max' => round($full, 2),
            'po_chosen' => round($this->payNow($rule, $full), 2),
            'po_id' => $idP,
            'po_url' => $this->context->link->getModuleLink($this->name, 'amount'),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/product.tpl');
    }

    public function hookDisplayShoppingCart(): string
    {
        $cart = $this->context->cart;
        if (!$cart || !$cart->id) {
            return '';
        }
        $rows = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_cart WHERE unit_pay < unit_full - 0.0001 AND id_cart = ' . (int) $cart->id) ?: [];
        if ($rows) {
            return '<div class="alert alert-info">' . htmlspecialchars($this->trans('This cart contains a pre-order deposit. You pay the deposit now and the balance when the product arrives; we will email you a payment link.', [], self::S)) . '</div>';
        }

        return $this->isBalanceCart((int) $cart->id)
            ? '<div class="alert alert-info">' . htmlspecialchars($this->trans('This is the remaining balance of your pre-order. Shipping is already paid.', [], self::S)) . '</div>'
            : '';
    }

    public function hookDisplayAdminOrderMain(array $params): string
    {
        $id = (int) ($params['id_order'] ?? 0);
        $rows = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_order WHERE id_order = ' . $id . ' OR id_balance_order = ' . $id) ?: [];
        if (!$rows) {
            return '';
        }
        $this->context->smarty->assign('po_rows', array_map(fn ($r) => $this->presentOrder($r), $rows));

        return $this->display(__FILE__, 'views/templates/admin/order.tpl');
    }

    // ------------------------------------------------------------------ back office
    private function statusLabel(string $s): string
    {
        return [
            'waiting' => $this->trans('Waiting for stock', [], self::D),
            'full' => $this->trans('Paid, waiting for stock', [], self::D),
            'notified' => $this->trans('Balance requested', [], self::D),
            'shipped' => $this->trans('Paid, stock arrived', [], self::D),
            'paid' => $this->trans('Balance paid', [], self::D),
        ][$s] ?? $s;
    }

    private function presentOrder(array $r): array
    {
        $order = new Order((int) $r['id_order']);
        $iso = (string) (new Currency((int) $order->id_currency))->iso_code;
        $due = in_array($r['status'], ['waiting', 'notified'], true) ? max(0, ((float) $r['unit_full'] - (float) $r['unit_paid']) * (int) $r['qty']) : 0;
        $r['product'] = Product::getProductName((int) $r['id_product'], (int) $r['id_product_attribute'], (int) $this->context->language->id);
        $r['status_label'] = $this->statusLabel($r['status']);
        $r['tone'] = ['waiting' => 'wait', 'full' => 'wait', 'notified' => 'action', 'shipped' => 'done', 'paid' => 'done'][$r['status']] ?? 'wait';
        $r['balance'] = $due > 0 ? $this->context->getCurrentLocale()->formatPrice($due, $iso) : '—';
        $r['reference'] = $order->reference;
        $r['date'] = Tools::displayDate($r['date_add']);
        $r['order_url'] = $this->context->link->getAdminLink('AdminOrders', true, ['route' => 'admin_orders_view', 'orderId' => (int) $r['id_order']], ['id_order' => (int) $r['id_order'], 'vieworder' => 1]);

        return $r;
    }

    /** Name, picture and price of a product for the back office lists and the live preview. */
    private function productCard(int $id): array
    {
        $lang = (int) $this->context->language->id;
        $product = new Product($id, false, $lang);
        $cover = Image::getCover($id);

        return [
            'id' => $id,
            'name' => (string) $product->name,
            'image' => $cover ? $this->context->link->getImageLink((string) $product->link_rewrite, (string) $cover['id_image'], ImageType::getFormattedName('small')) : '',
            'price' => round((float) Product::getPriceStatic($id, true, null, 2), 2),
        ];
    }

    private function modeLabel(array $r): string
    {
        $amount = $r['amount_type'] === 'percent'
            ? rtrim(rtrim(number_format((float) $r['amount'], 2, '.', ''), '0'), '.') . '%'
            : $this->context->getCurrentLocale()->formatPrice((float) $r['amount'], $this->context->currency->iso_code);

        return match ($r['mode']) {
            'partial' => $this->trans('Deposit', [], self::D) . ' · ' . $amount,
            'dynamic' => $this->trans('Customer chooses', [], self::D) . ' · min. ' . $amount,
            default => $this->trans('Full payment', [], self::D),
        };
    }

    /** on = selling as pre-order, partly = some combinations only, off = in stock. */
    private function stockState(int $id): string
    {
        $rows = Db::getInstance()->executeS('SELECT id_product_attribute, quantity FROM ' . _DB_PREFIX_ . 'stock_available WHERE id_product = ' . $id . ' AND id_shop = ' . (int) $this->context->shop->id) ?: [];
        $combos = array_filter($rows, fn ($r) => (int) $r['id_product_attribute'] > 0);
        $check = $combos ?: $rows;
        $empty = count(array_filter($check, fn ($r) => (int) $r['quantity'] <= 0));

        return $empty === 0 ? 'off' : ($empty === count($check) ? 'on' : 'partly');
    }

    public function getContent(): string
    {
        $out = '';
        $db = Db::getInstance();
        if (Tools::getValue('po_search') !== false && Tools::getValue('po_search') !== '') {
            header('Content-Type: application/json');
            $found = Product::searchByName((int) $this->context->language->id, (string) Tools::getValue('po_search')) ?: [];
            exit(json_encode(array_map(fn ($p) => $this->productCard((int) $p['id_product']), array_slice($found, 0, 10))));
        }
        if (Tools::isSubmit('po_save')) {
            $out .= $this->saveRule();
        }
        if ($id = (int) Tools::getValue('po_delete')) {
            $r = $db->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_rule WHERE id_product = ' . $id);
            if ($r) {
                $this->restoreStockPolicy($r);
                $db->delete('preorder_rule', 'id_product = ' . $id);
                $out .= $this->displayConfirmation($this->trans('Rule removed.', [], self::D));
            }
        }
        if ($id = (int) Tools::getValue('po_notify')) {
            $n = $this->notifyArrival($id);
            $out .= $this->displayConfirmation($this->trans('Customers notified: %count%', ['%count%' => $n], self::D));
        }
        $p = _DB_PREFIX_;
        $lang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $locale = $this->context->getCurrentLocale();
        $iso = $this->context->currency->iso_code;
        $money = fn ($v) => $locale->formatPrice((float) $v, $iso);

        $rules = [];
        foreach ($db->executeS("SELECT * FROM {$p}preorder_rule ORDER BY id_product DESC") ?: [] as $r) {
            $id = (int) $r['id_product'];
            $counts = $db->getRow("SELECT COALESCE(SUM(qty),0) reserved, COALESCE(SUM(IF(status IN ('waiting','full'), qty, 0)),0) waiting FROM {$p}preorder_order WHERE id_product = $id");
            $rules[] = $this->productCard($id) + [
                'mode' => $r['mode'], 'amount_type' => $r['amount_type'], 'amount' => (float) $r['amount'],
                'mode_label' => $this->modeLabel($r),
                'date' => $r['available_date'] ? date('Y-m-d\\TH:i', strtotime($r['available_date'])) : '',
                'date_label' => $r['available_date'] ? Tools::displayDate($r['available_date']) : '',
                'max' => (int) $r['max_qty'], 'groups' => array_filter(explode(',', $r['groups'])), 'countries' => array_filter(explode(',', $r['countries'])),
                'state' => $this->stockState($id), 'reserved' => (int) $counts['reserved'], 'waiting' => (int) $counts['waiting'],
            ];
        }
        // Amounts are stored in each order's currency; totals are shown in the shop's.
        $amounts = "SUM(o.qty * o.unit_full / ord.conversion_rate) value, SUM(o.qty * IF(o.status = 'paid', o.unit_full, o.unit_paid) / ord.conversion_rate) collected";
        $demand = $db->executeS("SELECT o.id_product, COUNT(DISTINCT o.id_customer) customers, SUM(o.qty) units, $amounts
            FROM {$p}preorder_order o JOIN {$p}orders ord ON (ord.id_order = o.id_order)
            GROUP BY o.id_product ORDER BY units DESC LIMIT 100") ?: [];
        $top = $demand ? max(array_column($demand, 'units')) : 1;
        $demand = array_map(fn ($d) => $this->productCard((int) $d['id_product']) + [
            'customers' => (int) $d['customers'], 'units' => (int) $d['units'], 'share' => round((int) $d['units'] / max(1, (int) $top) * 100),
            'value' => $money($d['value']), 'collected' => $money($d['collected']),
        ], $demand);
        $t = $db->getRow("SELECT COALESCE(SUM(o.qty),0) units, COALESCE(SUM(o.qty * o.unit_full / ord.conversion_rate),0) value,
                COALESCE(SUM(o.qty * IF(o.status = 'paid', o.unit_full, o.unit_paid) / ord.conversion_rate),0) collected,
                COALESCE(SUM(IF(o.status IN ('waiting','notified'), o.qty * (o.unit_full - o.unit_paid), 0) / ord.conversion_rate),0) due
            FROM {$p}preorder_order o JOIN {$p}orders ord ON (ord.id_order = o.id_order)");
        $orders = array_map(fn ($r) => $this->presentOrder($r), $db->executeS("SELECT * FROM {$p}preorder_order ORDER BY id_preorder DESC LIMIT 200") ?: []);
        $docs = is_file(__DIR__ . '/docs/preorder-' . $this->context->language->iso_code . '.pdf') ? $this->context->language->iso_code : 'en';
        $this->context->smarty->assign([
            'author_img' => $this->_path . 'views/img/author.jpg',
            'po_base' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            'po_rules' => $rules,
            'po_rules_json' => json_encode($rules, JSON_UNESCAPED_UNICODE),
            'po_demand' => $demand,
            'po_orders' => $orders,
            'po_groups' => Group::getGroups($lang),
            'po_countries' => Country::getCountries($lang, true),
            'po_units' => (int) $t['units'],
            'po_value' => $money($t['value']),
            'po_collected' => $money($t['collected']),
            'po_due' => $money($t['due']),
            'po_iso' => $iso,
            'po_locale' => $this->context->language->locale ?: 'en-US',
            'po_docs' => $this->_path . 'docs/preorder-' . $docs . '.pdf',
            'po_logo' => $this->_path . 'views/img/logo-512.png',
            'po_css' => $this->_path . 'views/css/admin.css?v=' . filemtime(__DIR__ . '/views/css/admin.css'),
            'po_js' => $this->_path . 'views/js/admin.js?v=' . filemtime(__DIR__ . '/views/js/admin.js'),
            'po_flash' => $out,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/author.tpl') . $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    private function saveRule(): string
    {
        $id = (int) Tools::getValue('po_product');
        if (!$id || !Validate::isLoadedObject(new Product($id))) {
            return $this->displayError($this->trans('Choose a product.', [], self::D));
        }
        $mode = in_array(Tools::getValue('po_mode'), ['full', 'partial', 'dynamic'], true) ? Tools::getValue('po_mode') : 'full';
        $type = Tools::getValue('po_amount_type') === 'fixed' ? 'fixed' : 'percent';
        $amount = (float) str_replace(',', '.', (string) Tools::getValue('po_amount'));
        if ($mode !== 'full' && ($amount <= 0 || ($type === 'percent' && $amount > 100))) {
            return $this->displayError($this->trans('Enter a deposit above zero (at most 100 for a percentage).', [], self::D));
        }
        $date = trim((string) Tools::getValue('po_date'));
        $date = $date && strtotime($date) ? date('Y-m-d H:i:s', strtotime($date)) : null;
        $ints = fn ($k) => implode(',', array_map('intval', (array) Tools::getValue($k, [])));
        $db = Db::getInstance();
        $existing = $db->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'preorder_rule WHERE id_product = ' . $id);
        $data = [
            'active' => 1, 'mode' => $mode, 'amount_type' => $type, 'amount' => self::money($amount),
            'available_date' => $date === null ? ['type' => 'sql', 'value' => 'NULL'] : pSQL($date), 'max_qty' => max(0, (int) Tools::getValue('po_max')),
            'groups' => pSQL($ints('po_groups')), 'countries' => pSQL($ints('po_countries')),
        ];
        $saved = $existing
            ? $db->update('preorder_rule', $data, 'id_product = ' . $id)
            : $db->insert('preorder_rule', $data + ['id_product' => $id]);
        if (!$saved) {
            return $this->displayError($db->getMsgError());
        }
        $this->setStockPolicy($id, $existing ?: []);

        return $this->displayConfirmation($this->trans('Settings updated.', [], self::D));
    }
}
