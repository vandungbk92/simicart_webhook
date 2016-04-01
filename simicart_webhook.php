<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Simicart_webhook extends Module
{
    protected $config_form = false;
    protected $url_simicart;
    public function __construct()
    {
        $this->name = 'simicart_webhook';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Simicart';
        $this->need_instance = 0;
        $this->url_simicart = Configuration::get('simicart_webhook');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
	
        parent::__construct();

        $this->displayName = $this->l('Simicart Webhook');
        $this->description = $this->l('This is Simicart Webhook, it help u sync data bettwen simicart and prestashop.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SIMICART_WEBHOOK_LIVE_MODE', false);

        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('actionCategoryAdd') &&
        $this->registerHook('actionCategoryDelete') &&
        $this->registerHook('actionCategoryUpdate') &&
        $this->registerHook('actionProductAdd') &&
        $this->registerHook('actionProductUpdate') &&
        $this->registerHook('actionProductDelete') &&
        $this->registerHook('actionCustomerAdd') &&
        $this->registerHook('actionCustomerUpdate') &&
        $this->registerHook('actionCustomerDelete') &&
        $this->registerHook('actionGroupUpdate') &&
        $this->registerHook('actionGroupAdd') &&
        $this->registerHook('actionGroupDelete') &&
        $this->registerHook('actionAddressUpdate') &&
        $this->registerHook('actionAddressAdd') &&
        $this->registerHook('actionAddressDelete') &&
        $this->registerHook('actionValidateOrder') &&
        $this->registerHook('actionOrderEdited') &&
        $this->registerHook('actionFeatureSave') &&
        $this->registerHook('actionFeatureDelete') &&
        $this->registerHook('actionFeatureValueSave') &&
        $this->registerHook('actionFeatureValueDelete') &&
        $this->registerHook('actionAttributeSave') &&
        $this->registerHook('actionAttributeDelete') &&
        $this->registerHook('actionAttributeGroupSave') &&
        $this->registerHook('actionAttributeGroupDelete') &&
        $this->registerHook('actionOrderStatusUpdate');

    }

    public function uninstall()
    {
        Configuration::deleteByName('SIMICART_WEBHOOK_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $my_module_name = strval(Tools::getValue('simicart_webhook'));
            if (!$my_module_name
                || empty($my_module_name))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('simicart_webhook', $my_module_name);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Webhook URL Simicart'),
                    'name' => 'simicart_webhook',
                    'size' => 200,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['simicart_webhook'] = Configuration::get('simicart_webhook');

        return $helper->generateForm($fields_form);
    }
    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSimicart_webhookModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SIMICART_WEBHOOK_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'SIMICART_WEBHOOK_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'SIMICART_WEBHOOK_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SIMICART_WEBHOOK_LIVE_MODE' => Configuration::get('SIMICART_WEBHOOK_LIVE_MODE', true),
            'SIMICART_WEBHOOK_ACCOUNT_EMAIL' => Configuration::get('SIMICART_WEBHOOK_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'SIMICART_WEBHOOK_ACCOUNT_PASSWORD' => Configuration::get('SIMICART_WEBHOOK_ACCOUNT_PASSWORD', null),
            'SIMICART_WEBHOOK_CONFIG' => Configuration::get('simicart_webhook', null)
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function fireWebhook($hook, $url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Type-Active:$hook"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_exec($ch);
        curl_close($ch);
    }

    public function hookActionCategoryAdd($params)
    {
        $this->fireWebhook('category.created',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCategoryDelete($params)
    {
        $this->fireWebhook('category.deleted',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCategoryUpdate($params)
    {
        $this->fireWebhook('category.updated',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductAdd($params)
    {
        $this->fireWebhook('product.created',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductUpdate($params)
    {
        $this->fireWebhook('product.updated',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductDelete($params)
    {
        $this->fireWebhook('product.deleted',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductAttributeAdd($params)
    {
        $this->fireWebhook('attribute.created',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductAttributeUpdate($params)
    {
        $this->fireWebhook('attribute.updated',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionProductAttributeDelete($params)
    {
        $this->fireWebhook('attribute.deleted',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $this->fireWebhook('actionCustomerAccountAdd',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCustomerAdd($params)
    {
        $this->fireWebhook('customer.created',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCustomerUpdate($params)
    {
        $this->fireWebhook('customer.updated',$this->url_simicart,array( 0 => $params ));
    }

    public function hookActionCustomerDelete($params)
    {
        $this->fireWebhook('customer.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionGroupUpdate($params)
    {
        $this->fireWebhook('group.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionGroupAdd($params)
    {
        $this->fireWebhook('group.created',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionGroupDelete($params)
    {
        $this->fireWebhook('group.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAddressUpdate($params)
    {
        $this->fireWebhook('address.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAddressAdd($params)
    {
        $this->fireWebhook('address.created',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAddressDelete($params)
    {
        $this->fireWebhook('address.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionValidateOrder($params)
    {
        $this->fireWebhook('Order.created',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionOrderEdited($params)
    {
        $this->fireWebhook('Order.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionFeatureSave($params)
    {
        $this->fireWebhook('Feature.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionFeatureDelete($params)
    {
        $this->fireWebhook('Feature.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionFeatureValueSave($params)
    {
        $this->fireWebhook('FeatureValue.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionFeatureValueDelete($params)
    {
        $this->fireWebhook('FeatureValue.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAttributeSave($params)
    {
        $this->fireWebhook('Option.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAttributeDelete($params)
    {
        $this->fireWebhook('Option.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAttributeGroupSave($params)
    {
        $this->fireWebhook('OptionValue.updated',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionAttributeGroupDelete($params)
    {
        $this->fireWebhook('OptionValue.deleted',$this->url_simicart,array( 0 => $params ));
    }
    public function hookActionOrderStatusUpdate($params)
    {
        $this->fireWebhook('Order.updated',$this->url_simicart,array( 0 => $params ));
    }
}
