<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Customer extends CustomerCore
{
    public function add($autodate = true, $null_values = true)
    {
        $this->id_shop = ($this->id_shop) ? $this->id_shop : Context::getContext()->shop->id;
        $this->id_shop_group = ($this->id_shop_group) ? $this->id_shop_group : Context::getContext()->shop->id_shop_group;
        $this->id_lang = ($this->id_lang) ? $this->id_lang : Context::getContext()->language->id;
        $this->birthday = (empty($this->years) ? $this->birthday : (int)$this->years.'-'.(int)$this->months.'-'.(int)$this->days);
        $this->secure_key = md5(uniqid(rand(), true));
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));

        if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }

        if ($this->id_default_group == Configuration::get('PS_CUSTOMER_GROUP')) {
            if ($this->is_guest) {
                $this->id_default_group = (int)Configuration::get('PS_GUEST_GROUP');
            } else {
                $this->id_default_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
            }
        }

        /* Can't create a guest customer, if this feature is disabled */
        if ($this->is_guest && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            return false;
        }
        $success = parent::add($autodate, $null_values);
        $this->updateGroup($this->groupBox);

        

        Hook::exec('actionCustomerAdd', array('customer' => $this));
        return $success;
    }

    public function update($nullValues = false)
    {
        $this->birthday = (empty($this->years) ? $this->birthday : (int)$this->years.'-'.(int)$this->months.'-'.(int)$this->days);

        if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }
        if (isset(Context::getContext()->controller) && Context::getContext()->controller->controller_type == 'admin') {
            $this->updateGroup($this->groupBox);
        }

        if ($this->deleted) {
            $addresses = $this->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
            foreach ($addresses as $address) {
                $obj = new Address((int)$address['id_address']);
                $obj->delete();
            }
        }
        Hook::exec('actionCustomerUpdate', array('customer' => $this));
        return parent::update(true);
    }

    public function delete()
    {
        if (!count(Order::getCustomerOrders((int)$this->id))) {
            $addresses = $this->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
            foreach ($addresses as $address) {
                $obj = new Address((int)$address['id_address']);
                $obj->delete();
            }
        }
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customer_group` WHERE `id_customer` = '.(int)$this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'message WHERE id_customer='.(int)$this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'specific_price WHERE id_customer='.(int)$this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'compare WHERE id_customer='.(int)$this->id);

        $carts = Db::getInstance()->executes('SELECT id_cart
															FROM '._DB_PREFIX_.'cart
															WHERE id_customer='.(int)$this->id);
        if ($carts) {
            foreach ($carts as $cart) {
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'cart WHERE id_cart='.(int)$cart['id_cart']);
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'cart_product WHERE id_cart='.(int)$cart['id_cart']);
            }
        }

        $cts = Db::getInstance()->executes('SELECT id_customer_thread
															FROM '._DB_PREFIX_.'customer_thread
															WHERE id_customer='.(int)$this->id);
        if ($cts) {
            foreach ($cts as $ct) {
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'customer_thread WHERE id_customer_thread='.(int)$ct['id_customer_thread']);
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'customer_message WHERE id_customer_thread='.(int)$ct['id_customer_thread']);
            }
        }
        CartRule::deleteByIdCustomer((int)$this->id);
        Hook::exec('actionCustomerDelete', array('customer' => $this));
        return parent::delete();
    }
}
