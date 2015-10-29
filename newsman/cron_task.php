<?php
/**
Copyright 2015 Dazoot Software

NOTICE OF LICENSE

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 
    @author Dramba Victor for Newsman
    @copyright 2015 Dazoot Software
    @license http://www.apache.org/licenses/LICENSE-2.0

*/

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/newsman.php');

$module = new Newsman();
if (!Module::isInstalled($module->name)) {
    exit;
}

$ret = $module->doSynchronize();
echo "$ret accounts added";
