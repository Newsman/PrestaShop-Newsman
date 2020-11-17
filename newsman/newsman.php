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
class Newsman extends Module
{
    const API_URL = 'https://ssl.newsman.ro/api/1.2/xmlrpc/';

    public function __construct()
    {
        $this->name = 'newsman';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'NewsmanApp Developers';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => _PS_VERSION_);
        $this->module_key = 'bb46dd134d42c2936ece1d3322d3a384';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Newsman');
        //TODO detailed description (in config.xml too)
        $this->description = $this->l(
            'The official Newsman module for PrestaShop. Manage your Newsman subscriber lists, map your shop groups to the Newsman segments.'
        );

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Newsman module?');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('NEWSMAN_DATA')
            && Configuration::deleteByName('NEWSMAN_MAPPING')
            && Configuration::deleteByName('NEWSMAN_CONNECTED')
            && Configuration::deleteByName('NEWSMAN_API_KEY')
            && Configuration::deleteByName('NEWSMAN_USER_ID')
            && Configuration::deleteByName('NEWSMAN_CRON');
    }

    public function getContent()
    {
        $connected = Configuration::get('NEWSMAN_CONNECTED');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        //$helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;

        // Load current value
        $helper->fields_value['api_key'] = Configuration::get('NEWSMAN_API_KEY');
        $helper->fields_value['user_id'] = Configuration::get('NEWSMAN_USER_ID');

        if(Configuration::get('NEWSMAN_JQUERY') == "on")
        {
            $helper->fields_value['jquery__'] = true;
        }
        else{
            $helper->fields_value['jquery__'] = false;
        }

        $helper->fields_value['cron_url'] = $this->context->shop->getBaseURL() . 'modules/newsman/cron_task.php';
        $helper->fields_value['cron_option'] = Configuration::get('NEWSMAN_CRON');

        $mappingSection = array(
            array(
                'type' => 'select',
                'label' => 'Newsman list',
                'name' => 'sel_list',
                'options' => array('query' => array())
            ),          
            array(
                'type' => 'checkbox',
                'label' => $this->l('Use jQuery'),
                'name' => 'jquery_',
                'values' => array(
                    'query' => '',
                    'id' => 'id',
                    'name' => 'jquery__',
                    'value' => '1'
                )
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l('              
                SYNC via CRON
                ')
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l('              
                {{limit}} = Sync with newsman from latest number of records (ex: 2000)
                ')
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l('              
                CRON Sync newsletter subscribers:
                ')
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l(
                 $this->context->shop->getBaseURL() . 'newsmanfetch.php?newsman=cron.json&cron=newsletter&apikey=' . $helper->fields_value['api_key'] . '&start=1&limit=2000&cronlast=true
                ')
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l('              
                CRON Sync customers with newsletter:
                ')
            ),
            array(
                'type' => 'html',
                'name' => 'unused',
                'html_content' => $this->l(
                    $this->context->shop->getBaseURL() . 'newsmanfetch.php?newsman=cron.json&cron=customers_newsletter&apikey=' . $helper->fields_value['api_key'] . '&start=1&limit=2000&cronlast=true
                ')
            )
        );

        /*
        //check for newsletter module
        if (Module::isInstalled('blocknewsletter')) {
            $mappingSection[] = array(
                'type' => 'select',
                'label' => $this->l('Newsletter subscribers'),
                'name' => 'map_newsletter',
                'class' => 'id-map-select',
                'options' => array('query' => array())
            );
        }
        //list groups
        foreach (Group::getGroups($default_lang) as $row) {
            if ($row['id_group'] < 3) {
                continue;
            }
            $mappingSection[] = array(
                'type' => 'select',
                'label' => $row['name'] . ' ' . $this->l('Group'),
                'name' => 'map_group_' . $row['id_group'],
                'class' => 'id-map-select',
                'options' => array('query' => array())
            );
        }
        */

        $out = '<div id="newsman-msg"></div>';
        $out .= $helper->generateForm(array(
            array('form' => array(
                'legend' => array(
                    'title' => $this->l('API Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('API KEY'),
                        'name' => 'api_key',
                        'size' => 40,
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('User ID'),
                        'name' => 'user_id',
                        'size' => 40,
                        'required' => true
                    )
                ),
                'buttons' => array(
                    array(
                        'title' => 'Connect',
                        'class' => 'pull-right',
                        'icon' => $connected ? 'process-icon-ok' : 'process-icon-next',
                        'js' => 'connectAPI(this)'
                    )
                )
            )),
            array('form' => array(
                'legend' => array(
                    'title' => $this->l('Synchronization mapping')
                ),
                'input' => $mappingSection,
                'buttons' => array(
                    array(
                        'title' => $this->l('Save mapping'),
                        'class' => 'pull-right',
                        'icon' => 'process-icon-save',
                        'js' => 'saveMapping(this)'
                    ),
                    array(
                        'title' => $this->l('Refresh segments'),
                        'icon' => 'process-icon-refresh',
                        'js' => 'connectAPI(this)'
                    )
                )
            )),

            /*
            array('form' => array(
                'legend' => array(
                    'title' => $this->l('Automatic synchronization')
                ),
                'input' => array(
                    array(
                        'label' => 'Automatic synchronization',
                        'type' => 'select',
                        'name' => 'cron_option',
                        'options' => array(
                            'query' => array(
                                array('value' => '', 'label' => $this->l('never (disabled)')),
                                array('value' => 'd', 'label' => $this->l('every day')),
                                array('value' => 'w', 'label' => $this->l('every week')),
                            ),
                            'id' => 'value',
                            'name' => 'label'
                        )
                    )
                ),
                'buttons' => array(
                    array(
                        'title' => $this->l('Synchronize now'),
                        'icon' => 'process-icon-next',
                        'js' => 'synchronizeNow(this)'
                    ),
                    array(
                        'title' => $this->l('Save option'),
                        'icon' => 'process-icon-save',
                        'class' => 'pull-right',
                        'js' => 'saveCron(this)'
                    ),

                )
            ))
           */

        ));

        //the script
        $this->context->controller->addJS($this->_path . 'views/js/newsman.js');

        $ajaxURL = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
        $mapExtra = array(
            //array('', $this->l('Do not import')),
            //array('none', $this->l('Import, no segment'))
        );
        $data = Configuration::get('NEWSMAN_DATA');
        $mapping = Configuration::get('NEWSMAN_MAPPING');

        $out .= '<script>var newsman=' . Tools::jsonEncode(array(
                'data' => $data ? Tools::jsonDecode($data) : false,
                'mapExtra' => $mapExtra,
                'mapping' => $mapping ? Tools::jsonDecode($mapping) : false,
                'ajaxURL' => $ajaxURL,
                'strings' => array(
                    'needConnect' => $this->l('You need to connect to Newsman first!'),
                    'needMapping' => $this->l('You need to save mapping first!')
                )
            )) . '</script>';

        return $out;
    }

    private function jsonOut($output)
    {
        header('Content-Type: application/json');
        echo Tools::jsonEncode($output);
    }

    public function ajaxProcessConnect()
    {
        $output = array();
        Configuration::updateValue('NEWSMAN_CONNECTED', 0);
        Configuration::deleteByName('NEWSMAN_DATA');
        $api_key = Tools::getValue('api_key');
        $user_id = Tools::getValue('user_id');
        if (!Validate::isGenericName($api_key) || $api_key == '') {
            $output['msg'][] = $this->displayError($this->l('Invalid value for API KEY'));
        }
        if (!Validate::isInt($user_id)) {
            $output['msg'][] = $this->displayError($this->l('Invalid value for UserID'));
        }
        if (!isset($output['msg']) || !count($output['msg'])) {
            $client = $this->getClient($user_id, $api_key);
            if ($client->query('list.all')) {
                $output['lists'] = $client->getResponse();

                Configuration::updateValue('NEWSMAN_API_KEY', $api_key);
                Configuration::updateValue('NEWSMAN_USER_ID', $user_id);
                Configuration::updateValue('NEWSMAN_CONNECTED', 1);
                $output['msg'][] = $this->displayConfirmation($this->l('Connected. Please choose the synchronization details below.'));
                $output['ok'] = true;
                //get segments for the first list
                $list_id = $output['lists'][0]['list_id'];
                $client->query('segment.all', $list_id);
                $output['segments'] = $client->getResponse();
                //save lists and segments
                Configuration::updateValue(
                    'NEWSMAN_DATA',
                    Tools::jsonEncode(array('lists' => $output['lists'], 'segments' => $output['segments']))
                );
                $output['saved'] = 'saved';
            } else {
                $output['msg'][] = $this->displayError(
                    $this->l('Error connecting. Please check your API KEY and user ID.') . "<br>" .
                    $client->getErrorMessage()
                );
            }
        }
        $this->jsonOut($output);
    }

    public function ajaxProcessSaveMapping()
    {
        require_once dirname(__FILE__) . '/lib/Client.php';
        
        $client = new Newsman_Client(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));

        $jquery = Tools::getValue('jquery');     

        $mapping = Tools::getValue('mapping');

        //Generate feed        
        $list = (array)json_decode($mapping);
        $list = $list["list"];
        $url = Context::getContext()->shop->getBaseURL(true) . "newsmanfetch.php?newsman=products.json&apikey=" . Configuration::get('NEWSMAN_API_KEY');			      
        $ret = $client->feeds->setFeedOnList($list, $url, Context::getContext()->shop->getBaseURL(true), "NewsMAN");	

        Configuration::updateValue('NEWSMAN_MAPPING', $mapping);
        Configuration::updateValue('NEWSMAN_JQUERY', $jquery);
        $this->jsonOut(true);
    }

    private function getClient($user_id, $api_key)
    {
        require_once dirname(__FILE__) . '/lib/XMLRPC.php';
        return new XMLRPC_Client(self::API_URL . "$user_id/$api_key");
    }

    public function ajaxProcessSynchronize()
    {
        $x = $this->doSynchronize();

        if ($x == 0) {
            $this->jsonOut(array('msg' =>
                $this->displayError($this->l('Make sure you have a SEGMENT created in Newsman, after that make sure you SAVE MAPPING with your SEGMENT'))));
            return;
        } else {
            $this->jsonOut(array('msg' =>
                $this->displayConfirmation($this->l('Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.'))));
        }
    }

    public function ajaxProcessListChanged()
    {
        $list_id = Tools::getValue('list_id');
        $client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));
        $client->query('segment.all', $list_id);
        $output = array();
        $output['segments'] = $client->getResponse();

        $client->query('list.all');
        $output['lists'] = $client->getResponse();
        Configuration::updateValue(
            'NEWSMAN_DATA',
            Tools::jsonEncode(array('lists' => $output['lists'], 'segments' => $output['segments']))
        );

        $this->jsonOut($output);
    }

    public function ajaxProcessSaveCron()
    {
        $option = Tools::getValue('option');
        if (!$option || Module::isInstalled('cronjobs') && function_exists('curl_init')) {
            $this->jsonOut(array('msg' => $this->displayConfirmation($this->l('Automatic synchronization option saved.'))));
            Configuration::updateValue('NEWSMAN_CRON', $option);
            if ($option) {
                $this->registerHook('actionCronJob');
            } else {
                $this->unregisterHook('actionCronJob');
            }
        } else {
            $this->unregisterHook('actionCronJob');
            Configuration::updateValue('NEWSMAN_CRON', '');
            $this->jsonOut(
                array(
                    'fail' => true,
                    'msg' => $this->displayError(
                        $this->l(
                            'To enable automatic synchronization you need to install ' .
                            'and configure "Cron tasks manager" module from PrestaShop.'
                        )
                    )
                )
            );
        }
    }

    public function getCronFrequency()
    {
        $option = Configuration::get('NEWSMAN_CRON');
        return array(
            'hour' => '1',
            'day' => '-1',
            'month' => '-1',
            'day_of_week' => $option == 'd' ? '-1' : '1'
        );
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

        $client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));

        $mapping = Tools::jsonDecode($mappingData, true);
        $list_id = $mapping['list'];
        $count = 0;

        $value = $mapping['map_newsletter'];
        if (Module::isInstalled('blocknewsletter')) {
            //search on blocknewsletter module
            $dbq = new DbQuery();
            $q = $dbq->select('`email`')
                ->from('newsletter')
                ->where('`active` = 1');
            $ret = Db::getInstance()->executeS($q->build());
            $count += count($ret);
            $header = "email,prestashop_source";
            $lines = array();
            foreach ($ret as $row) {
                $lines[] = "{$row['email']},newsletter";
            }
            //upload from newsletter
            $segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;
            if ($segment_id != null) {
                array($segment_id);
            } else {
                $segment_id = array();
            }

            $this->exportCSV($client, $list_id, $segment_id, $header, $lines);
        }

        if (Module::isInstalled('ps_emailsubscription') || Module::isInstalled("emailsubscription")) {
            //search on emailsubscription module
            $dbq = new DbQuery();
            $q = $dbq->select('`email`, `newsletter_date_add`')
                ->from('emailsubscription')
                ->where('`active` = 1');
            $ret = Db::getInstance()->executeS($q->build());
            $count += count($ret);
            $header = "email, newsletter_date_add, source";
            $lines = array();
            foreach ($ret as $row) {
                $lines[] = "{$row['email']}, {$row["newsletter_date_add"]}, prestashop 1.6-1.7 plugin newsletter active";
            }
            //upload from newsletter
            $segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;
            if ($segment_id != null) {
                array($segment_id);
            } else {
                $segment_id = array();
            }

            $this->exportCSV($client, $list_id, $segment_id, $header, $lines);
        }

        if ($value) {
            //search on customer
            $dbq = new DbQuery();
            $q = $dbq->select('`email`, `firstname`, `lastname`, `id_gender`, `birthday`')
                ->from('customer')
                ->where('`newsletter` = 1');
            $ret = Db::getInstance()->executeS($q->build());

            $count += count($ret);

            $header = "email,firstname,lastname,gender,birthday,source";
            $lines = array();
            foreach ($ret as $row) {

                $gender = ($row["gender"] == "1") ? "Barbat" : "Femeie";

                $lines[] = "{$row['email']},{$row['firstname']},{$row['lastname']}, {$gender}, {$row["birthday"]}, prestashop 1.6-1.7 plugin customer with newsletter";
            }
            $segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;
            $this->exportCSV($client, $list_id, array($segment_id), $header, $lines);
        }

        foreach ($mapping as $key => $value) {
            if (!$value) {
                continue;
            }
            if (Tools::substr($key, 0, 10) !== 'map_group_') {
                continue;
            }
            $id_group = (int)(Tools::substr($key, 10));
            $dbq = new DbQuery();
            $q = $dbq->select('c.email, c.firstname, c.lastname, c.id_gender, c.birthday')
                ->from('customer', 'c')
                ->leftJoin('customer_group', 'cg', 'cg.id_customer=c.id_customer')
                ->where('cg.id_group=' . $id_group)
                ->where('c.newsletter=1');

            $ret = Db::getInstance()->executeS($q->build());

            if (count($ret)) {
                $count += count($ret);
                $cols = array_keys($ret[0]);
                //rename id_gender
                $cols[3] = "gender";

                $header = join(',', $cols) . ",prestashop 1.6-1.7 plugin customers with newsletter by prestashop groups";

                //rename gender again to be filtered
                $cols[3] = "id_gender";

                $lines = array();
                foreach ($ret as $row) {
                    $line = '';
                    foreach ($cols as $col) {

                        if ($col == "id_gender") {
                            if ($row[$col] == "1") {
                                $row[$col] = "Barbat";
                            } else if ($row[$col] == "2") {
                                $row[$col] = "Femeie";
                            }
                        }

                        $line .= $row[$col] . ',';
                    }
                    $lines[] = "$line,group_{$id_group}";
                }

                //upload group
                $segment_id = Tools::substr($value, 0, 4) == 'seg_' ? Tools::substr($value, 4) : null;

                $this->exportCSV($client, $list_id, array($segment_id), $header, $lines);
            }
        }
        return $count;
    }

    private function exportCSV($client, $list_id, $segments, $header, $lines)
    {
        //clear segments
        /*if (!empty($segments))
        {
            $ret = $client->query('segment.clear', $segments[0]);
        }*/

        $max = 10000;
        for ($i = 0; $i < count($lines); $i += $max) {
            $a = array_slice($lines, $i, $max);
            array_unshift($a, $header);
            //$ret = $client->query('import.schedulecsv', $list_id, $segments, join("\n", $a), 600);
            $ret = $client->query('import.csv', $list_id, $segments, join("\n", $a));
        }
    }
}
