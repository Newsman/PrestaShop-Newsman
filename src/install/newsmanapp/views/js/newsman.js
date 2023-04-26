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

(function () {

    //export
    self.saveCron = saveCron;
    self.connectAPI = connectAPI;
    self.saveMapping = saveMapping;
    self.synchronizeNow = synchronizeNow;

    var data, mapExtra, mapping, ajaxURL, strings;

    var debug = self.console && console.debug ? console.debug.bind(console) : $.noop;

    $(function () {

        data = newsman.data;
        mapExtra = newsman.mapExtra;
        mapping = newsman.mapping;
        ajaxURL = newsman.ajaxURL;
        strings = newsman.strings;

        if (data)
            updateSelects();
      
        if(mapping)
        {                  
            ajaxCall('ListChanged', {list_id: mapping.list}, function (ret) {
                data.segments = ret.segments;
                updateSelects();          
            });
        }

        $('#notificationsM .close').on('click', function(){
            $('#notificationsM').hide();
        });

        $('#sel_list').change(function () {        
            var $me = $(this);
            var $ld = $('<i class="process-icon-loading" style="display: inline-block"/>');
            $me.css({display: 'inline-block'}).after($ld);
            mapping.list = $me.val();
            ajaxCall('ListChanged', {list_id: mapping.list}, function (ret) {
                data.segments = ret.segments;
                updateSelects();
                $ld.remove();
            });
        })
    });

    function updateSelects() {
        //list select
        $('#sel_list').empty().append(data.lists.map(function (item) {
            return $('<option/>').attr('value', item.list_id).text(item.list_name);
        }));      

        if(data.segments != null)
        {
            if(!data.segments.hasOwnProperty("faultCode"))
            {             
                //segment selects
                $('#sel_segment').empty().append(data.segments.map(function (item) {
                    return $('<option/>').attr('value', item.segment_id).text(item.segment_name);
                }));
            }
        }

        $('.id-map-select').each(function () {
            $(this).empty().append(options.map(function (item) {
                return $('<option/>').attr('value', item[0]).text(item[1]);
            }))
        });

        if (mapping) {           
            $('#sel_list').val(mapping.list);
            $('#sel_segment').val(mapping.segment);
        }
    }

    function ajaxCall(action, vars, ready) {
        $.ajax({
            url: ajaxURL,
            data: $.extend({ajax: true, action: action}, vars),
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
            notification(ret.msg);
            data = {lists: ret.lists, segments: ret.segments};
            updateSelects();
        });
    }

    function notification(msg){
        $('#notificationsMMessage').html(msg);
        $('#notificationsM').show();

        setTimeout(function(){
            $('#notificationsM').hide();
        }, 4000);
    }

    function saveMapping(btn) {

        if (!data) {
            return alert(strings.needConnect);
        }
        
        var icn = btn.querySelector('i');
        icn.className = 'process-icon-loading';
        mapping = {
            list: $('#sel_list').val(),
            segment: $('#sel_segment').val(),
            remarketingid: $('#newsman_account_id').val(),
            remarketingenabled: $('input[name="newsman_userid"]:checked').val()
        };

        ajaxCall('SaveMapping', {mapping: JSON.stringify(mapping)}, function (ret) {
            icn.className = 'process-icon-ok';            
            notification(ret);
        });
    }

    function synchronizeNow(btn) {
        if (!mapping)
            return alert(strings.needMapping);

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
        ajaxCall('SaveCron', {option: $('#cron_option').val()}, function (ret) {
            $('#newsman-msg').html(ret.msg);
            $('body').animate({scrollTop: 0}, 300);
            icn.className = 'process-icon-ok';
            if (ret.fail) {
                $('#cron_option').val('');
            }
        });
    }

})();