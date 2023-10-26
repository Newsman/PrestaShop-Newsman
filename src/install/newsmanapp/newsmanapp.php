<?php
/**
 * Copyright 2015 Dazoot Software
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Newsman Developers
 * @copyright 2015 Dazoot Software
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Newsmanapp extends Module
{
    public const API_URL = 'https://ssl.newsman.ro/api/1.2/xmlrpc/';

    protected $js_state = 0;
    protected $eligible = 0;
    protected $filterable = 1;
    protected static $products = [];
    protected $_debug = 0;

    public function __construct()
    {
        $this->name = 'newsmanapp';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'NewsmanApp Developers';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.4', 'max' => _PS_VERSION_];
        $this->module_key = '6ba80d421c0725052ed456896aabd823';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('NewsmanApp');
        $this->description = $this->l(
            'The official NewsmanApp module for PrestaShop. Manage your Newsman subscriber lists, map your shop groups to NewsmanApp segments.'
        );

        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall Newsman module?'
        );
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName('NEWSMAN_DATA') &&
            Configuration::deleteByName('NEWSMAN_MAPPING') &&
            Configuration::deleteByName('NEWSMAN_CONNECTED') &&
            Configuration::deleteByName('NEWSMAN_API_KEY') &&
            Configuration::deleteByName('NEWSMAN_USER_ID') &&
            Configuration::deleteByName('NEWSMAN_CRON');
    }

    public function install()
    {
        if (
            version_compare(_PS_VERSION_, '1.5', '>=') &&
            Shop::isFeatureActive()
        ) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if(version_compare(_PS_VERSION_, '1.5', '>=') && version_compare(_PS_VERSION_, '8', '<'))
        {
            return parent::install() &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('header') &&
            $this->registerHook('footer') &&
            $this->registerHook('productfooter') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('actionOrderStatusUpdate');
        }
        elseif(version_compare(_PS_VERSION_, '8', '>=')){
            return parent::install() &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('actionOrderStatusUpdate');
        }
    }

    public function getContent()
    {   
        $connected = Configuration::get('NEWSMAN_CONNECTED');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex =
            AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        // Get default Language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;

        // Load current value
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        $mappingDecoded = json_decode($mapping, true);

        // the script
        $this->context->controller->addJS($this->_path . 'views/js/newsman.js');

        $ajaxURL =
            $this->context->link->getAdminLink('AdminModules') .
            '&configure=' .
            $this->name;

        $mapExtra = [];
        $data = Configuration::get('NEWSMAN_DATA');

        $js = 'var newsman=' .
            json_encode([
                'data' => $data ? json_decode($data) : false,
                'mapExtra' => $mapExtra,
                'mapping' => $mapping ? json_decode($mapping) : false,
                'ajaxURL' => $ajaxURL,
                'strings' => [
                    'needConnect' => $this->l(
                        'You need to connect to Newsman first!'
                    ),
                    'needMapping' => $this->l(
                        'You need to save mapping first!'
                    ),
                ],
            ]);

        $frontend = [
            'js' => $js,
            'list' => $mappingDecoded['list'] ?? '',
            'segment' => $mappingDecoded['segment'] ?? '',
            'remarketingid' => $mappingDecoded['remarketingid'] ?? '',
            'remarketingenabled' => $mappingDecoded['remarketingenabled'] ?? '',
            'apikey' => Configuration::get('NEWSMAN_API_KEY'),
            'userid' => Configuration::get('NEWSMAN_USER_ID'),
            'cron1' => $this->context->shop->getBaseURL() . 'napi?newsman=cron.json&cron=customers_newsletter&apikey=' . Configuration::get('NEWSMAN_API_KEY') . '&start=1&limit=2000&cronlast=true',
            'cron2' => $this->context->shop->getBaseURL() . 'napi?newsman=cron.json&cron=newsletter&apikey=' . Configuration::get('NEWSMAN_API_KEY') . '&start=1&limit=2000&cronlast=true',
        ];

        $this->smarty->assign('newsmanapp', $frontend);

        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }

    private function jsonOut($output)
    {
        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public function ajaxProcessConnect()
    {
        $output = [];
        Configuration::updateValue('NEWSMAN_CONNECTED', 0);
        Configuration::deleteByName('NEWSMAN_DATA');
        $api_key = Tools::getValue('api_key');
        $user_id = Tools::getValue('user_id');
        if (!Validate::isGenericName($api_key) || $api_key == '') {
            $output['msg'][] = 'Invalid value for API KEY';
        }
        if (!Validate::isInt($user_id)) {
            $output['msg'][] = 'Invalid value for UserID';
        }
        if (!isset($output['msg']) || !count($output['msg'])) {
            $client = $this->getClient($user_id, $api_key);
            $lists = $client->list->all();
            if (!empty($lists)) {       
                $output['lists'] = $lists;

                $connected = 1;
                $message = 'Connected. Please choose the synchronization details below.';

                if (array_key_exists('faultString', $output['lists'])) {
                    $message = $output['lists']['faultString'];
                    $connected = 0;
                }

                Configuration::updateValue('NEWSMAN_API_KEY', $api_key);
                Configuration::updateValue('NEWSMAN_USER_ID', $user_id);
                Configuration::updateValue('NEWSMAN_CONNECTED', $connected);
                $output['msg'][] = $message;
                $output['ok'] = true;
                // get segments for the first list
                $list_id = $output['lists'][0]['list_id'];
                $output['segments'] = $client->segment->all($list_id);
                // save lists and segments
                Configuration::updateValue(
                    'NEWSMAN_DATA',
                    json_encode([
                        'lists' => $output['lists'],
                        'segments' => $output['segments'],
                    ])
                );
                $output['saved'] = 'saved';
            } else {
                $output['msg'][] = 'Error connecting. Please check your API KEY and user ID.';
            }
        }
        $this->jsonOut($output);
    }

    public function ajaxProcessSaveMapping()
    {
        require_once dirname(__FILE__) . '/lib/Client.php';

        $msg = 'Mapping saved successfully.';

        $client = new Newsman_Client(
            Configuration::get('NEWSMAN_USER_ID'),
            Configuration::get('NEWSMAN_API_KEY')
        );

        $connected = Configuration::get('NEWSMAN_CONNECTED');
        if ($connected == 0) {
            $this->jsonOut('Newsman credentials User id & Api key are incorrect.');

            return;
        }

        $mapping = Tools::getValue('mapping');

        // Generate feed
        $mappingDecoded = json_decode($mapping, true);

        $list = $mappingDecoded['list'];
        $url = Context::getContext()->shop->getBaseURL(true) .
            'napi?newsman=products.json&apikey=' .
            Configuration::get('NEWSMAN_API_KEY');

        try {
            $ret = $client->feeds->setFeedOnList(
                $list,
                $url,
                Context::getContext()->shop->getBaseURL(true),
                'NewsMAN'
            );
        } catch (Exception $e) {
        }

        Configuration::updateValue('NEWSMAN_MAPPING', $mapping);

        $this->jsonOut($msg);
    }

    private function getClient($user_id, $api_key)
    {
        //require_once dirname(__FILE__) . '/lib/XMLRPC.php';

        //return new XMLRPC_Client(self::API_URL . "$user_id/$api_key");
        
        require_once dirname(__FILE__) . '/lib/Client.php';
        
        return new Newsman_Client(
        $user_id,
        $api_key
        );
    }

    public function ajaxProcessSynchronize()
    {
        $res = $this->doSynchronize();

        if ($res == 0) {
            $this->jsonOut([
                'msg' => 'Make sure you have a SEGMENT created in Newsman, after that make sure you SAVE MAPPING with your SEGMENT',
            ]);

            return;
        } else {
            $this->jsonOut([
                'msg' => 'Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.',
            ]);
        }
    }

    public function ajaxProcessListChanged()
    {
        $list_id = Tools::getValue('list_id');
        $client = $this->getClient(
            Configuration::get('NEWSMAN_USER_ID'),
            Configuration::get('NEWSMAN_API_KEY')
        );
        
        $output = [];
        $output['segments'] = $client->segment->all($list_id);

        $output['lists'] = $client->list->all();
        Configuration::updateValue(
            'NEWSMAN_DATA',
            json_encode([
                'lists' => $output['lists'],
                'segments' => $output['segments'],
            ])
        );

        $this->jsonOut($output);
    }

    public function ajaxProcessSaveCron()
    {
        $option = Tools::getValue('option');
        if (
            !$option ||
            (Module::isInstalled('cronjobs') && function_exists('curl_init'))
        ) {
            $this->jsonOut([
                'msg' => 'Automatic synchronization option saved.',
            ]);
            Configuration::updateValue('NEWSMAN_CRON', $option);
            if ($option) {
                $this->registerHook('actionCronJob');
            } else {
                $this->unregisterHook('actionCronJob');
            }
        } else {
            $this->unregisterHook('actionCronJob');

            Configuration::updateValue('NEWSMAN_CRON', '');

            $this->jsonOut([
                'fail' => true,
                'msg' => 'To enable automatic synchronization you need to install ' .
                'and configure "Cron tasks manager" module from PrestaShop.',
            ]);
        }
    }

    public function getCronFrequency()
    {
        $option = Configuration::get('NEWSMAN_CRON');

        return [
            'hour' => '1',
            'day' => '-1',
            'month' => '-1',
            'day_of_week' => $option == 'd' ? '-1' : '1',
        ];
    }

    public function hookModuleRoutes($params)
    {
        return [
            'module-newsmanapp-napi' => [
                'controller' => 'napi',
                'rule' => 'napi',
                'keywords' => [
                    'link_rewrite' => [
                        'regexp' => "[_a-zA-Z0-9-\pL]*",
                        'param' => 'link_rewrite',
                    ],
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'newsmanapp',
                ],
            ],
        ];
    }

    public function actionCronJob()
    {
        $this->doSynchronize();
    }

    public function doSynchronize()
    {
        $mappingData = Configuration::get('NEWSMAN_MAPPING');

        if (!Configuration::get('NEWSMAN_CONNECTED') || !$mappingData) {
            return 0;
        }

        $client = $this->getClient(
            Configuration::get('NEWSMAN_USER_ID'),
            Configuration::get('NEWSMAN_API_KEY')
        );

        $mapping = json_decode($mappingData, true);
        $list_id = $mapping['list'];
        $count = 0;

        $value = $mapping['map_newsletter'];
        if (Module::isInstalled('blocknewsletter')) {
            // search on blocknewsletter module
            $dbq = new DbQuery();
            $q = $dbq
                ->select('`email`')
                ->from('newsletter')
                ->where('`active` = 1');
            $ret = Db::getInstance()->executeS($q->build());
            $count += count($ret);
            $header = 'email,prestashop_source';
            $lines = [];
            foreach ($ret as $row) {
                $lines[] = "{$row['email']},newsletter";
            }
            // upload from newsletter
            $segment_id =
                Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_'
                    ? Tools::substr($mapping['map_newsletter'], 4)
                    : null;
            if ($segment_id != null) {
                [$segment_id];
            } else {
                $segment_id = [];
            }

            $this->exportCSV($client, $list_id, $segment_id, $header, $lines);
        }

        if (
            Module::isInstalled('ps_emailsubscription') ||
            Module::isInstalled('emailsubscription')
        ) {
            // search on emailsubscription module
            $dbq = new DbQuery();
            $q = $dbq
                ->select('`email`, `newsletter_date_add`')
                ->from('emailsubscription')
                ->where('`active` = 1');
            $ret = Db::getInstance()->executeS($q->build());
            $count += count($ret);
            $header = 'email, newsletter_date_add, source';
            $lines = [];
            foreach ($ret as $row) {
                $lines[] = "{$row['email']}, {$row['newsletter_date_add']}, prestashop 1.6-1.7 plugin newsletter active";
            }
            // upload from newsletter
            $segment_id =
                Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_'
                    ? Tools::substr($mapping['map_newsletter'], 4)
                    : null;
            if ($segment_id != null) {
                [$segment_id];
            } else {
                $segment_id = [];
            }

            $this->exportCSV($client, $list_id, $segment_id, $header, $lines);
        }

        if ($value) {
            // search on customer
            $dbq = new DbQuery();
            $q = $dbq
                ->select(
                    '`email`, `firstname`, `lastname`, `id_gender`, `birthday`'
                )
                ->from('customer')
                ->where('`newsletter` = 1');
            $ret = Db::getInstance()->executeS($q->build());

            $count += count($ret);

            $header = 'email,firstname,lastname,gender,birthday,source';
            $lines = [];
            foreach ($ret as $row) {
                $gender = $row['gender'] == '1' ? 'Barbat' : 'Femeie';

                $lines[] = "{$row['email']},{$row['firstname']},{$row['lastname']}, {$gender}, {$row['birthday']}, prestashop plugin";
            }
            $segment_id =
                Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_'
                    ? Tools::substr($mapping['map_newsletter'], 4)
                    : null;
            $this->exportCSV($client, $list_id, [$segment_id], $header, $lines);
        }

        foreach ($mapping as $key => $value) {
            if (!$value) {
                continue;
            }
            if (Tools::substr($key, 0, 10) !== 'map_group_') {
                continue;
            }
            $id_group = (int) Tools::substr($key, 10);
            $dbq = new DbQuery();
            $q = $dbq
                ->select(
                    'c.email, c.firstname, c.lastname, c.id_gender, c.birthday'
                )
                ->from('customer', 'c')
                ->leftJoin(
                    'customer_group',
                    'cg',
                    'cg.id_customer=c.id_customer'
                )
                ->where('cg.id_group=' . $id_group)
                ->where('c.newsletter=1');

            $ret = Db::getInstance()->executeS($q->build());

            if (count($ret)) {
                $count += count($ret);
                $cols = array_keys($ret[0]);
                // rename id_gender
                $cols[3] = 'gender';

                $header = join(',', $cols) . ',prestashop plugin';

                // rename gender again to be filtered
                $cols[3] = 'id_gender';

                $lines = [];
                foreach ($ret as $row) {
                    $line = '';
                    foreach ($cols as $col) {
                        if ($col == 'id_gender') {
                            if ($row[$col] == '1') {
                                $row[$col] = 'Barbat';
                            } elseif ($row[$col] == '2') {
                                $row[$col] = 'Femeie';
                            }
                        }

                        $line .= $row[$col] . ',';
                    }
                    $lines[] = "$line,group_{$id_group}";
                }

                // upload group
                $segment_id =
                    Tools::substr($value, 0, 4) == 'seg_'
                        ? Tools::substr($value, 4)
                        : null;

                $this->exportCSV(
                    $client,
                    $list_id,
                    [$segment_id],
                    $header,
                    $lines
                );
            }
        }

        return $count;
    }

    private function exportCSV($client, $list_id, $segments, $header, $lines)
    {
        $max = 10000;
        for ($i = 0; $i < count($lines); $i += $max) {
            $a = array_slice($lines, $i, $max);
            array_unshift($a, $header);
            $ret = $client->query(
                'import.csv',
                $list_id,
                $segments,
                join("\n", $a)
            );
        }
    }

    public static $endpoint = 'https://retargeting.newsmanapp.com/js/retargeting/track.js';
    public static $endpointHost = 'https://retargeting.newsmanapp.com';

    protected function _getGoogleAnalyticsTag($back_office = false)
    {
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        $mappingDecoded = json_decode($mapping, true);

        $ga_id = $mappingDecoded['remarketingid'];

        $controller_name = Tools::getValue('controller');

        if (strpos($controller_name, 'Admin') !== false) {
            return '';
        }

        $ga_snippet_head =
            "
            <script type=\"text/javascript\">
            //Newsman remarketing tracking code REPLACEABLE

            var remarketingid = '$ga_id';
            var _nzmPluginInfo = '1.2:prestashop';
            
            //Newsman remarketing tracking code REPLACEABLE
    
            //Newsman remarketing tracking code  
    
            var endpoint = 'https://retargeting.newsmanapp.com';
            var remarketingEndpoint = endpoint + '/js/retargeting/track.js';
    
            var _nzm = _nzm || [];
            var _nzm_config = _nzm_config || [];
            _nzm_config['disable_datalayer'] = 1;
            _nzm_tracking_server = endpoint;
            (function() {
                var a, methods, i;
                a = function(f) {
                    return function() {
                        _nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
                    }
                };
                methods = ['identify', 'track', 'run'];
                for (i = 0; i < methods.length; i++) {
                    _nzm[methods[i]] = a(methods[i])
                };
                s = document.getElementsByTagName('script')[0];
                var script_dom = document.createElement('script');
                script_dom.async = true;
                script_dom.id = 'nzm-tracker';
                script_dom.setAttribute('data-site-id', remarketingid);
                script_dom.src = remarketingEndpoint;
                //check for engine name
                if (_nzmPluginInfo.indexOf('shopify') !== -1) {
                    script_dom.onload = function(){
                        if (typeof newsmanRemarketingLoad === 'function')
                            newsmanRemarketingLoad();
                    }
                }
                s.parentNode.insertBefore(script_dom, s);
            })();
            _nzm.run('require', 'ec');
    
            //Newsman remarketing tracking code   
        let newsmanVersion = '" .
            _PS_VERSION_ .
            "';
        </script>
        ";

        $ga_snippet_head .= '
        <script type="text/javascript" src="/modules/newsmanapp/views/js/NewsmanRemarketingActionLib.js?t=' . time() . '"></script>     
        ';

        return $ga_snippet_head;
    }

    public function hookHeader()
    {
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        $mappingDecoded = json_decode($mapping, true);

        if (isset($mappingDecoded) && array_key_exists("remarketingenabled", $mappingDecoded) && $mappingDecoded['remarketingenabled'] == '1') {
            $nzm = $this->_getGoogleAnalyticsTag();

            return $nzm;
        }
    }

    public function hookDisplayHeader()
    {
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        $mappingDecoded = json_decode($mapping, true);

        if (isset($mappingDecoded) && array_key_exists("remarketingenabled", $mappingDecoded) && $mappingDecoded['remarketingenabled'] == '1') {
            $nzm = $this->_getGoogleAnalyticsTag();

            return $nzm;
        }
    }

    /**
     * Return a detailed transaction for Newsman Remarketing
     */
    public function wrapOrder($id_order)
    {
        $order = new Order((int) $id_order);

        if (Validate::isLoadedObject($order)) {
            return [
                'id' => $id_order,
                'affiliation' => Shop::isFeatureActive()
                    ? $this->context->shop->name
                    : Configuration::get('PS_SHOP_NAME'),
                'revenue' => $order->total_paid,
                'shipping' => $order->total_shipping,
                'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                'url' => $this->context->link->getAdminLink('AdminNewsmanAjax'),
                'customer' => $order->id_customer,
            ];
        }
    }

    /**
     * To track transactions
     */
    public function hookOrderConfirmation($params)
    {
        $order = $params['objOrder'];
        if (
            Validate::isLoadedObject($order) &&
            $order->getCurrentState() != (int) Configuration::get('PS_OS_ERROR')
        ) {
            if ($order->id_customer == $this->context->cookie->id_customer) {
                $order_products = [];
                $cart = new Cart($order->id_cart);
                foreach ($cart->getProducts() as $order_product) {
                    $category = new Category(
                        (int) $order_product['id_category_default'],
                        (int) $this->context->language->id
                    );
                    $order_product['category_name'] = $category->name;
                    $order_products[] = $this->wrapProduct(
                        $order_product,
                        [],
                        0,
                        true
                    );
                }

                $id_cust = $order->id_customer;
                $customer = new Customer($id_cust);

                $transaction = [
                    'id' => $order->id,
                    'email' => $customer->email,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,
                    'affiliation' => version_compare(_PS_VERSION_, '1.5', '>=') &&
                        Shop::isFeatureActive()
                            ? $this->context->shop->name
                            : Configuration::get('PS_SHOP_NAME'),
                    'revenue' => $order->total_paid,
                    'shipping' => $order->total_shipping,
                    'tax' => $order->total_paid_tax_incl -
                        $order->total_paid_tax_excl,
                    'url' => $this->context->link->getModuleLink(
                        'newsmanremarketing',
                        'ajax',
                        [],
                        true
                    ),
                    'customer' => $order->id_customer,
                ];
                $ga_scripts = $this->addTransaction(
                    $order_products,
                    $transaction
                );

                $this->js_state = 1;

                return $this->_runJs($ga_scripts);
            }
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        require_once dirname(__FILE__) . '/lib/Client.php';

        $client = new Newsman_Client(
            Configuration::get('NEWSMAN_USER_ID'),
            Configuration::get('NEWSMAN_API_KEY')
        );

        $mapping = Configuration::get('NEWSMAN_MAPPING');

        // Generate feed
        $mappingDecoded = json_decode($mapping, true);

        if(!empty($mappingDecoded))
        {
            $list = (array_key_exists("list", $mappingDecoded)) ? $mappingDecoded["list"] : "";
            
            if(!empty($list))
            {
                try {
                        $ret = $client->remarketing->setPurchaseStatus(
                            $list,
                            $order->id,
                            $params['newOrderStatus']->name
                        );
                    } catch (Exception $e) {
                }
            }
        }
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = null;

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $order = $params['order'];
        } else {
            $order = $params['objOrder'];
        }

        $order_products = [];
        $cart = new Cart($order->id_cart);
        foreach ($cart->getProducts() as $order_product) {
            $category = new Category(
                (int) $order_product['id_category_default'],
                (int) $this->context->language->id
            );
            $order_product['category_name'] = $category->name;
            $order_products[] = $this->wrapProduct($order_product, [], 0, true);
        }

        $id_cust = $order->id_customer;
        $customer = new Customer($id_cust);

        $transaction = [
            'id' => $order->id,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'affiliation' => version_compare(_PS_VERSION_, '1.5', '>=') &&
                Shop::isFeatureActive()
                    ? $this->context->shop->name
                    : Configuration::get('PS_SHOP_NAME'),
            'revenue' => $order->total_paid,
            'shipping' => $order->total_shipping,
            'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
            'url' => $this->context->link->getModuleLink(
                'newsmanremarketing',
                'ajax',
                [],
                true
            ),
            'customer' => $order->id_customer,
        ];
        $ga_scripts = $this->addTransaction($order_products, $transaction);

        $this->js_state = 1;

        return $this->_runJs($ga_scripts);
    }

    /**
     * hook footer to load JS script for standards actions such as product clicks
     */
    public function hookFooter()
    {
        $ga_scripts = '';
        $this->js_state = 0;

        if (isset($this->context->cookie->ga_cart)) {
            $this->filterable = 0;

            $gacarts = unserialize($this->context->cookie->ga_cart);
            foreach ($gacarts as $gacart) {
                if ($gacart['quantity'] > 0) {
                } elseif ($gacart['quantity'] < 0) {
                    $gacart['quantity'] = abs($gacart['quantity']);
                }
            }
            unset($this->context->cookie->ga_cart);
        }

        $controller_name = Tools::getValue('controller');
        $products = $this->wrapProducts(
            $this->context->smarty->getTemplateVars('products'),
            [],
            true
        );

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->eligible = 1;
            $step = Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if ($controller_name == 'orderconfirmation') {
                $this->eligible = 1;
            }
        } else {
            $confirmation_hook_id = (int) Hook::getIdByName(
                'orderConfirmation'
            );
            if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
                $this->eligible = 1;
            }
        }

        if (
            isset($products) &&
            count($products) &&
            $controller_name != 'index'
        ) {
            if ($this->eligible == 0) {
                $ga_scripts .= $this->addProductImpression($products);
            }
            $ga_scripts .= $this->addProductClick($products);
        }

        return $this->_runJs($ga_scripts);
    }

    public function hookDisplayFooter()
    {
        $ga_scripts = '';
        $this->js_state = 0;

        if (isset($this->context->cookie->ga_cart)) {
            $this->filterable = 0;

            $gacarts = unserialize($this->context->cookie->ga_cart);
            foreach ($gacarts as $gacart) {
                if ($gacart['quantity'] > 0) {
                } elseif ($gacart['quantity'] < 0) {
                    $gacart['quantity'] = abs($gacart['quantity']);
                }
            }
            unset($this->context->cookie->ga_cart);
        }

        $controller_name = Tools::getValue('controller');
        $products = $this->wrapProducts(
            $this->context->smarty->getTemplateVars('products'),
            [],
            true
        );

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->eligible = 1;
            $step = Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if ($controller_name == 'orderconfirmation') {
                $this->eligible = 1;
            }
        } else {
            $confirmation_hook_id = (int) Hook::getIdByName(
                'orderConfirmation'
            );
            if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
                $this->eligible = 1;
            }
        }

        if (
            isset($products) &&
            count($products) &&
            $controller_name != 'index'
        ) {
            if ($this->eligible == 0) {
                $ga_scripts .= $this->addProductImpression($products);
            }
            $ga_scripts .= $this->addProductClick($products);
        }

        return $this->_runJs($ga_scripts);
    }

    protected function filter($ga_scripts)
    {
        if ($this->filterable = 1) {
            return implode(';', array_unique(explode(';', $ga_scripts)));
        }

        return $ga_scripts;
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     */
    public function hookHome()
    {
        $ga_scripts = '';

        // Home featured products
        if ($this->isModuleEnabled('homefeatured')) {
            $category = new Category(
                $this->context->shop->getCategory(),
                $this->context->language->id
            );
            $home_featured_products = $this->wrapProducts(
                $category->getProducts(
                    (int) Context::getContext()->language->id,
                    1,
                    Configuration::get('HOME_FEATURED_NBR')
                        ? (int) Configuration::get('HOME_FEATURED_NBR')
                        : 8,
                    'position'
                ),
                [],
                true
            );
            $ga_scripts .=
                $this->addProductImpression($home_featured_products) .
                $this->addProductClick($home_featured_products);
        }

        // New products
        if (
            $this->isModuleEnabled('blocknewproducts') &&
            (Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ||
                Configuration::get('PS_BLOCK_NEWPRODUCTS_DISPLAY'))
        ) {
            $new_products = Product::getNewProducts(
                (int) $this->context->language->id,
                0,
                (int) Configuration::get('NEW_PRODUCTS_NBR')
            );
            $new_products_list = $this->wrapProducts($new_products, [], true);
            $ga_scripts .=
                $this->addProductImpression($new_products_list) .
                $this->addProductClick($new_products_list);
        }

        // Best Sellers
        if (
            $this->isModuleEnabled('blockbestsellers') &&
            (!Configuration::get('PS_CATALOG_MODE') ||
                Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY'))
        ) {
            $ga_homebestsell_product_list = $this->wrapProducts(
                ProductSale::getBestSalesLight(
                    (int) $this->context->language->id,
                    0,
                    8
                ),
                [],
                true
            );
            $ga_scripts .=
                $this->addProductImpression($ga_homebestsell_product_list) .
                $this->addProductClick($ga_homebestsell_product_list);
        }

        $this->js_state = 1;

        return $this->_runJs($this->filter($ga_scripts));
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     */
    public function isModuleEnabled($name)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Module::isEnabled($name)) {
                $module = Module::getInstanceByName($name);

                return $module->isRegisteredInHook('home');
            } else {
                return false;
            }
        } else {
            $module = Module::getInstanceByName($name);

            return $module && $module->active === true;
        }
    }

    /**
     * wrap products to provide a standard products information for Newsman Remarketing script
     */
    public function wrapProducts($products, $extras = [], $full = false)
    {
        $result_products = [];
        if (!is_array($products)) {
            return;
        }

        $currency = new Currency($this->context->currency->id);
        $usetax =
            Product::getTaxCalculationMethod(
                (int) $this->context->customer->id
            ) != PS_TAX_EXC;

        if (count($products) > 20) {
            $full = false;
        } else {
            $full = true;
        }

        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $product = (array) $product;
            }

            if (!isset($product['price'])) {
                $product['price'] = (float) Tools::displayPrice(
                    Product::getPriceStatic(
                        (int) $product['id_product'],
                        $usetax
                    ),
                    $currency
                );
            }
            $result_products[] = $this->wrapProduct(
                $product,
                $extras,
                $index,
                $full
            );
        }

        return $result_products;
    }

    /**
     * wrap product to provide a standard product information for Newsman Remarketing script
     */
    public function wrapProduct($product, $extras, $index = 0, $full = false)
    {
        $ga_product = '';

        $variant = null;
        if (isset($product['attributes_small'])) {
            $variant = $product['attributes_small'];
        } elseif (isset($extras['attributes_small'])) {
            $variant = $extras['attributes_small'];
        }

        $product_qty = 1;
        if (isset($extras['qty'])) {
            $product_qty = $extras['qty'];
        } elseif (isset($product['cart_quantity'])) {
            $product_qty = $product['cart_quantity'];
        }

        $product_id = 0;
        if (!empty($product['id_product'])) {
            $product_id = $product['id_product'];
        } else {
            if (!empty($product['id'])) {
                $product_id = $product['id'];
            }
        }

        $product_type = 'typical';
        if (isset($product['pack']) && $product['pack'] == 1) {
            $product_type = 'pack';
        } elseif (isset($product['virtual']) && $product['virtual'] == 1) {
            $product_type = 'virtual';
        }

        $price = 0;
        $formatPrice = (isset($product["price"])) ? $product["price"] : 0;
        $formatAmount = (isset($product["price_amount"])) ? $product['price_amount'] : 0;

        if (version_compare(_PS_VERSION_, '1.7', '>=') && version_compare(_PS_VERSION_, '8', '<')) {
            if (isset($product['price_amount'])) {
                $price = number_format($formatAmount, '2');
            } else {
                $price = number_format($formatPrice, '2');
            }
        }

        if (version_compare(_PS_VERSION_, '8', '>=')) {
            $price = number_format($formatAmount, '2');
        }

        $price = str_replace(',', '', $price);

        if (!empty($product['category_name'])) {
            $product['category'] = $product['category_name'];
        }

        if ($full) {
            $ga_product = [
                'id' => '' . $product_id . '',
                'name' => json_encode($product['name']),
                'category' => json_encode($product['category']),
                'brand' => isset($product['manufacturer_name'])
                    ? json_encode($product['manufacturer_name'])
                    : '',
                'variant' => json_encode($variant),
                'type' => $product_type,
                'position' => $index ? $index : 0,
                'quantity' => $product_qty,
                'list' => Tools::getValue('controller'),
                'url' => isset($product['link'])
                    ? urlencode($product['link'])
                    : '',
                'price' => (float) $price,
            ];
        } else {
            $ga_product = [
                'id' => $product_id,
                'name' => json_encode($product['name']),
            ];
        }

        return $ga_product;
    }

    /**
     * add order transaction
     */
    public function addTransaction($products, $order)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'NMBG.add(' . json_encode($product) . ');';
        }

        $controller_name = Tools::getValue('controller');

        return $js . 'NMBG.addTransaction(' . json_encode($order) . ');';
    }

    /**
     * add product impression js and product click js
     */
    public function addProductImpression($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'NMBG.add(' . json_encode($product) . ",'',true);";
        }

        return $js;
    }

    public function addProductClick($products)
    {
    }

    public function addProductClickByHttpReferal($products)
    {
    }

    /**
     * Add product checkout info
     */
    public function addProductFromCheckout($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'NMBG.add(' . json_encode($product) . ');';
        }

        return $js;
    }

    /**
     * hook product page footer to load JS for product details view
     */
    public function hookProductFooter($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product') {
            $paramProd = null;

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $paramProd = $params['product'];
            } else {
                $paramProd = (array) $params['product'];
            }

            $category = new Category(
                (int) $paramProd['id_category_default'],
                (int) $this->context->language->id
            );
            $paramProd['category_name'] = $category->name;

            $ga_product = $this->wrapProduct($paramProd, null, 0, true);
            $js =
                'NMBG.addProductDetailView(' .
                json_encode($ga_product) .
                ');';

            if (
                isset($_SERVER['HTTP_REFERER']) &&
                strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0
            ) {
                if ($this->context->cookie->prodclick != $ga_product['name']) {
                    $this->context->cookie->prodclick = $ga_product['name'];

                    $js .= $this->addProductClickByHttpReferal([$ga_product]);
                }
            }

            $this->js_state = 1;

            return $this->_runJs($js);
        }
    }

    public function hookDisplayFooterProduct($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product') {
            $paramProd = null;

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $paramProd = $params['product'];
            } else {
                $paramProd = (array) $params['product'];
            }

            $category = new Category(
                (int) $paramProd['id_category_default'],
                (int) $this->context->language->id
            );
            $paramProd['category_name'] = $category->name;

            $ga_product = $this->wrapProduct($paramProd, null, 0, true);
            $js =
                'NMBG.addProductDetailView(' .
                json_encode($ga_product) .
                ');';

            if (
                isset($_SERVER['HTTP_REFERER']) &&
                strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0
            ) {
                if ($this->context->cookie->prodclick != $ga_product['name']) {
                    $this->context->cookie->prodclick = $ga_product['name'];

                    $js .= $this->addProductClickByHttpReferal([$ga_product]);
                }
            }

            $this->js_state = 1;

            return $this->_runJs($js);
        }
    }

    /**
     * Generate Newsman Remarketing js
     */
    protected function _runJs($js_code, $backoffice = 0)
    {
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        $mappingDecoded = json_decode($mapping, true);

        if (!empty($mappingDecoded['remarketingid'])) {
            $runjs_code = '';
            if (!empty($js_code)) {
                $runjs_code .=
                    '
                
                <script type="text/javascript">
                
document.addEventListener("DOMContentLoaded", function(event) {
    
                 function cJ()
                {
                    if (jLoadedNewsman) {
                    
                      var NMBG = NewsmanAnalyticEnhancedECommerce;

                      ' .
                    $js_code .
                    '
                      
                    }
                    else{
                        setTimeout(function(){
                                cJ();
                            }, 1000);
                    }
                }

                cJ();
      
               });
                 
                </script>';
            }

            if ($this->js_state != 1 && $backoffice == 0) {
                $controller_name = Tools::getValue('controller');

                if (strpos($controller_name, 'Admin') !== false) {
                    return $runjs_code;
                }
            }

            return $runjs_code;
        }
    }
}
