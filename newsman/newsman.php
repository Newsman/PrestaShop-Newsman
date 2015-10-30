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
 *  @author    Dramba Victor for Newsman
 *  @copyright 2015 Dazoot Software
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

class Newsman extends Module
{
    const API_URL = 'https://ssl.newsman.ro/api/1.2/xmlrpc/';

    public function __construct() {
        $this->name = 'newsman';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Victor Dramba for Newsman';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => _PS_VERSION_);
        $this->module_key = 'bb46dd134d42c2936ece1d3322d3a384';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Newsman');
        //TODO detailed description (in config.xml too)
        $this->description = $this->l(
	        'The official Newsman module for PrestaShop. ' .
	        'Manage your Newsman subscriber lists, map your shop groups to the Newsman segments.'
        );

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Newsman module?');
    }

    public function uninstall() {
        return parent::uninstall()
            && Configuration::deleteByName('NEWSMAN_DATA')
            && Configuration::deleteByName('NEWSMAN_MAPPING')
            && Configuration::deleteByName('NEWSMAN_CONNECTED')
            && Configuration::deleteByName('NEWSMAN_API_KEY')
            && Configuration::deleteByName('NEWSMAN_USER_ID')
            && Configuration::deleteByName('NEWSMAN_CRON');
    }

    public function getContent() {
        $connected = Configuration::get('NEWSMAN_CONNECTED');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        //$helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;

        // Load current value
        $helper->fields_value['api_key'] = Configuration::get('NEWSMAN_API_KEY');
        $helper->fields_value['user_id'] = Configuration::get('NEWSMAN_USER_ID');
        $helper->fields_value['cron_url'] = $this->context->shop->getBaseURL() . 'modules/newsman/cron_task.php';
        $helper->fields_value['cron_option'] = Configuration::get('NEWSMAN_CRON');

        $mappingSection = array(
            array(
                'type' => 'select',
                'label' => 'Newsman list',
                'name' => 'sel_list'
            ),
            array(
                'type' => 'html',
                'html_content' => $this->l('Newsman destination segment')
            )
        );

        //check for newsletter module
        if (Module::isInstalled('blocknewsletter')) {
            $mappingSection[] = array(
                'type' => 'select',
                'label' => $this->l('Newsletter subscribers'),
                'name' => 'map_newsletter',
                'class' => 'id-map-select'
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
                'name' => 'map_group_'.$row['id_group'],
                'class' => 'id-map-select'
            );
        }

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
                    ),
                    /*array(
                        'label' => 'Cron URL',
                        'type' => 'text',
                        'name' => 'cron_url'
                    )*/
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
        ));

        //the script
        $ajaxURL = $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name;
        $mapExtra = array(
            array('', $this->l('Do not import')),
            array('none', $this->l('Import, no segment'))
        );
        $data = Configuration::get('NEWSMAN_DATA');
        $mapping = Configuration::get('NEWSMAN_MAPPING');
        ob_start();
        ?>
		<script>
	    var debug = self.console && console.debug ? console.debug.bind(console) : $.noop;
	    var data = <?=$data ? $data : 'false'?>;
	    var mapExtra = <?=Tools::jsonEncode($mapExtra)?>;
	    var mapping = <?=$mapping ? $mapping : 'false'?>;

	    function updateSelects() {
	        //list select
	        $('#sel_list').empty().append(data.lists.map(function (item) {
	            return $('<option/>').attr('value', item.list_id).text(item.list_name);
	        }));

	        //segment selects
	        var options = mapExtra.concat(data.segments.map(function (item) {
	            return ['seg_'+item.segment_id, item.segment_name];
	        }));

	        $('.id-map-select').each(function () {
	            $(this).empty().append(options.map(function (item) {
	                return $('<option/>').attr('value', item[0]).text(item[1]);
	            }))
	        });
	        if (mapping) {
	            $('#sel_list').val(mapping.list);
	            $('.id-map-select').each(function () {
	                $(this).val(mapping[$(this).attr('name')]);
	                if ($(this).val() == null) $(this).val('');
	            })
	        }
	    }
	    if (data) {
		    $(updateSelects);
	    }

	    $(function () {
	        $('#sel_list').change(function () {
	            var $me = $(this);
	            var $ld = $('<i class="process-icon-loading" style="display: inline-block"/>');
	            $me.css({display:'inline-block'}).after($ld);
	            mapping.list = $me.val();
	            ajaxCall('ListChanged', {list_id: mapping.list}, function (ret) {
	                data.segments = ret.segments;
	                updateSelects();
	                $ld.remove();
	            });
	        })
	    });

	    function ajaxCall(action, vars, ready) {
	        $.ajax({
	            url: '<?=$ajaxURL?>',
	            data: $.extend({ajax:true, action: action}, vars),
	            success: ready
	        });
	    }

	    function connectAPI(btn) {
	        var icn = btn.querySelector('i');
	        icn.className = 'process-icon-loading';
	        ajaxCall('Connect', {
	            api_key: $('#api_key').val(),
	            user_id: $('#user_id').val()
	        }, function (ret) {
	            debug(ret);
	            icn.className = ret.ok ? 'process-icon-ok' : 'process-icon-next';
	            $('#newsman-msg').html(ret.msg);
	            data = {lists: ret.lists, segments: ret.segments};
	            updateSelects();
	        });
	    }

	    function saveMapping(btn) {
	        if (!data) {
		        return alert('<?=$this->l('You need to connect to Newsman first!')?>');
	        }
	        var icn = btn.querySelector('i');
	        icn.className = 'process-icon-loading';
	        mapping = {
	            list: $('#sel_list').val()
	        };
	        $('.id-map-select').each(function () {
	            mapping[$(this).attr('name')] = $(this).val();
	        });
	        ajaxCall('SaveMapping', {mapping: JSON.stringify(mapping)}, function (ret) {
	            icn.className = 'process-icon-ok';
	        });
	    }

	    function synchronizeNow(btn) {
	        if (!mapping) {
		        return alert('<?=$this->l('You need to save mapping first!')?>');
	        }
	        var icn = btn.querySelector('i');
	        icn.className = 'process-icon-loading';
	        ajaxCall('Synchronize', {}, function (ret) {
	            icn.className = 'process-icon-ok';
	            debug(ret);
	            $('body').animate({scrollTop: 0}, 300);
	            $('#newsman-msg').html(ret.msg);
	        });
	    }

	    function saveCron(btn) {
	        var icn = btn.querySelector('i');
	        icn.className = 'process-icon-loading';
	        ajaxCall('SaveCron', {option:$('#cron_option').val()}, function (ret) {
	            $('#newsman-msg').html(ret.msg);
	            $('body').animate({scrollTop: 0}, 300);
	            icn.className = 'process-icon-ok';
	            if (ret.fail) {
		            $('#cron_option').val('');
	            }
	        });
	    }
		</script>
<?php
        $out .= ob_get_clean();
        return $out;
    }

    private function jsonOut($output) {
        header('Content-Type: application/json');
        echo Tools::jsonEncode($output);
    }

    public function ajaxProcessConnect() {
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
        if (!count($output['msg'])) {
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

    public function ajaxProcessSaveMapping() {
        $mapping = Tools::getValue('mapping');
        Configuration::updateValue('NEWSMAN_MAPPING', $mapping);
        $this->jsonOut(true);
    }

    private function getClient($user_id, $api_key) {
        require_once dirname(__FILE__) . '/lib/XMLRPC.php';
        return new XMLRPC_Client(self::API_URL . "$user_id/$api_key");
    }

    public function ajaxProcessSynchronize() {
        $this->doSynchronize();
        $this->jsonOut(array('msg' =>
            $this->displayConfirmation($this->l('Users uploaded and scheduled for import. It might take a few minutes until they show up in your Newsman lists.'))));
    }

    public function ajaxProcessListChanged() {
        $list_id = Tools::getValue('list_id');
        $client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));
        $client->query('segment.all', $list_id);
        $output = array();
        $output['segments'] = $client->getResponse();
        $this->jsonOut($output);
    }

    public function ajaxProcessSaveCron() {
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
		            'fail'=>true,
		            'msg'=> $this->displayError(
			            $this->l('To enable automatic synchronization you need to install '.
			                'and configure "Cron tasks manager" module from PrestaShop.'
			            )
		            )
	            )
            );
        }
    }

    public function getCronFrequency() {
        $option = Configuration::get('NEWSMAN_CRON');
        return array(
            'hour' => '1',
            'day' => '-1',
            'month' => '-1',
            'day_of_week' => $option == 'd' ? '-1' : '1'
        );
    }

    public function actionCronJob() {
        $this->doSynchronize();
    }

    public function doSynchronize() {
        $mappingData = Configuration::get('NEWSMAN_MAPPING');
        if (!Configuration::get('NEWSMAN_CONNECTED') || !$mappingData) {
	        return 0;
        }

        $client = $this->getClient(Configuration::get('NEWSMAN_USER_ID'), Configuration::get('NEWSMAN_API_KEY'));

        $mapping = Tools::jsonDecode($mappingData, true);
        $list_id = $mapping['list'];
        $count = 0;
        //newsletter
        $value = $mapping['map_newsletter'];
        if ($value && Module::isInstalled('blocknewsletter')) {
            $q = (new DbQuery())
                ->select('`email`')
                ->from('newsletter')
                ->where('`active` = 1');
            $ret = Db::getInstance()->executeS($q->build());
            $count += count($ret);
            $csv = "email,prestashop_source\n";
            foreach ($ret as $row) {
	            $csv .= "{$row['email']},newsletter\n";
            }
            //upload from newsletter
            $segment_id = Tools::substr($mapping['map_newsletter'], 0, 4) == 'seg_' ? Tools::substr($mapping['map_newsletter'], 4) : null;
            $client->query('import.csv', $list_id, array($segment_id), $csv);
            //$import_id[] = $client->getResponse();
        }
        foreach ($mapping as $key => $value) {
            if (!$value) {
	            continue;
            }
            if (Tools::substr($key, 0, 10) !== 'map_group_') {
	            continue;
            }
            $id_group = (int) (Tools::substr($key, 10));
            $q = (new DbQuery())
                ->select('c.email, c.firstname, c.lastname')
                ->from('customer', 'c')
                ->leftJoin('customer_group', 'cg', 'cg.id_customer=c.id_customer')
                ->where('cg.id_group='.$id_group);
            $ret = Db::getInstance()->executeS($q->build());
            if (count($ret)) {
                $count += count($ret);
                $cols = array_keys($ret[0]);
                $csv = join(',', $cols) . ",prestashop_source\n";
                foreach ($ret as $row) {
                    foreach ($cols as $col) {
	                    $csv .= $row[$col] . ',';
                    }
                    $csv .= "group_{$id_group}\n";
                }
                //upload group
                $segment_id = Tools::substr($value, 0, 4) == 'seg_' ? Tools::substr($value, 4) : null;
                $client->query('import.csv', $list_id, array($segment_id), $csv);
                //$import_id[] = $client->getResponse();
            }
        }
        return $count;
    }
}
