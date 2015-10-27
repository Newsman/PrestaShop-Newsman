<?php
require_once(dirname(__FILE__).'/../../config/config.inc.php');

require_once(dirname(__FILE__) . '/newsman.php');

$module = new Newsman();
if (!Module::isInstalled($module->name))
	exit;

$ret = $module->doSynchronize();
print_r($ret);