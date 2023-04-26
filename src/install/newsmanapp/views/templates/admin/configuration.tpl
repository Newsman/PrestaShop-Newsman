<!--
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
 -->
<div id="configuration_form" class="defaultForm form-horizontal newsmanapp">
   <div class="panel" id="fieldset_0">
      <div class="panel-heading">
        <i class="icon-cogs"></i>API Settings
      </div>
      <div class="form-wrapper">
         <div class="form-group">
            <label class="control-label col-lg-4 required">
            API KEY
            </label>
            <div class="col-lg-8">
               <input type="text" name="api_key" id="api_key" value="{$newsmanapp.apikey}" class="" size="40" required="required">
            </div>
         </div>
         <div class="form-group">
            <label class="control-label col-lg-4 required">
            User ID
            </label>
            <div class="col-lg-8">
               <input type="text" name="user_id" id="user_id" value="{$newsmanapp.userid}" class="" size="40" required="required">
            </div>
         </div>
      </div>
      <div class="panel-footer">
         <button type="button" class="btn btn-default pull-right" name="submitOptionsconfiguration" onclick="connectAPI(this)"><i class="process-icon-ok"></i> Connect</button>
      </div>
   </div>
   <div class="panel" id="fieldset_1">
      <div class="panel-heading">
         Synchronization mapping / Remarketing
      </div>
      <div class="form-wrapper">
         <div class="form-group">
            <label class="control-label col-lg-4">
<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="This information is available in your Newsman Remarketing account">
            Newsman Remarketing Tracking ID
            </span>
            </label>
            <div class="col-lg-8">
               <input type="text" name="newsman_account_id" id="newsman_account_id" value="{$newsmanapp.remarketingid}" class="" size="20">
            </div>
         </div>
         <div class="form-group">
            <label class="control-label col-lg-4">
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="The User ID is set at the property level. To find a property go to your newsman account">
            Newsman Remarketing User ID
            </span>
            </label>
            <div class="col-lg-8">
               <div class="radio ">
                  <label><input type="radio" name="newsman_userid" id="newsman_userid_enabled" value="1" {if $newsmanapp.remarketingenabled == 1}checked="checked"{/if}>Enabled</label>
               </div>
               <div class="radio ">
                  <label><input type="radio" name="newsman_userid" id="newsman_userid_disabled" value="0" {if $newsmanapp.remarketingenabled == 0}checked="checked"{/if}>Disabled</label>
               </div>
            </div>
         </div>
         <div class="form-group">
            <label class="control-label col-lg-4">
            Newsman list
            </label>
            <div class="col-lg-8">
               <select name="sel_list" class=" fixed-width-xl" id="sel_list">
               </select>
            </div>
         </div>
         <div class="form-group">
            <label class="control-label col-lg-4">
            Newsman segment
            </label>
            <div class="col-lg-8">
               <select name="sel_segment" class=" fixed-width-xl" id="sel_segment">
               </select>
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               SYNC via CRON
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               limit = Sync with newsman from latest number of records (ex: 2000)
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               CRON Sync newsletter subscribers: 
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               {$newsmanapp.cron1}
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               CRON Sync customers with newsletter:
            </div>
         </div>
         <div class="form-group">
            <div class="col-lg-8 col-lg-offset-3">
               {$newsmanapp.cron2}
            </div>
         </div>
      </div>
      <div class="panel-footer">
         <button type="button" class="btn btn-default pull-right" name="submitOptionsconfiguration" onclick="saveMapping(this)"><i class="process-icon-save"></i> Save mapping</button>
         <button type="button" class="btn btn-default" name="submitOptionsconfiguration" onclick="connectAPI(this)"><i class="process-icon-refresh"></i> Refresh segments</button>
      </div>
   </div>
</form>

<div class="modal" id="notificationsM" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Newsmanapp notifications</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="notificationsMMessage"></p>
      </div>
    </div>
  </div>
</div>

<script>
    {$newsmanapp.js}
</script>