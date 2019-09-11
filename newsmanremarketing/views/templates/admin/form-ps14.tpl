<?php
/**
* Copyright 2019 Dazoot Software
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
* @author    Lucian for Newsman
* @copyright 2019 Dazoot Software
* @license   http://www.apache.org/licenses/LICENSE-2.0
*/
?>
<form enctype="multipart/form-data" method="post" class="defaultForm newsmanremarketing" id="configuration_form">
	<fieldset id="fieldset_0">
		<legend>
			Paramètres
		</legend>

		<label>Newsman Remarketing ID </label>

		<div class="margin-form">
			<input type="text" size="20" class="" value="{$account_id|escape:'htmlall':'UTF-8'}" id="GA_ACCOUNT_ID" name="GA_ACCOUNT_ID">&nbsp;<sup>*</sup>
			<span name="help_box" class="hint" style="display: none;">This information is available in your Newsman (https://www.newsman.app) account<span class="hint-pointer"></span></span>
		</div>
		<div class="clear"></div>

		<div class="margin-form">
			<input class="button" type="submit" name="submitnewsmanremarketing" value="{l s='Save' mod='newsmanremarketing'}" id="configuration_form_submit_btn">
		</div>

		<div class="small"><sup>*</sup> Champ requis</div>
	</fieldset>
</form>
