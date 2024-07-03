    <link rel='stylesheet' href='<?php echo base_url('dist/select2.min.css'); ?>'>
    <script src='<?php echo base_url('dist/select2.min.js'); ?>'></script>

<div id="required_fields_message">
    <?php echo lang('common_lang.common_fields_required_message'); ?>
</div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('counters/save/' . $person_info->person_id, array('id' => 'counter_form', 'class' => 'form-horizontal')); ?>
<ul class="nav nav-tabs nav-justified" data-tabs="tabs">
    <li class="active" role="presentation">
        <a data-toggle="tab"
           href="#counter_basic_info"><?php echo lang("counters_lang.counters_basic_information"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab" href="#counter_login_info"><?php echo lang("counters_lang.counters_login_info"); ?></a>
    </li>
    <li role="presentation" id="production_tab" <?php echo (empty($data['selected_items']) && $person_info->category!=2) ? 'style="display: none;"' : ''; ?>>
        <a data-toggle="tab" href="#counter_items_info"><?php echo lang("counters_lang.counters_items_info"); ?></a>
    </li>
</ul>
<?php if ($person_info->person_id) { ?>
    <script type="text/javascript">
        $("input[name=category]").attr({
            readonly:"readonly", 
            onclick:"return false",
        });
    </script>
<?php } ?>
<div class="tab-content">
    <div class="tab-pane fade in active" id="counter_basic_info">
        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('counters_lang.counters_category'), 'category', array('class'=>'required control-label col-xs-3')); ?>
                <div class="col-xs-8">
                    <label class="radio-inline">
                        <?php echo form_radio(array(
                                'name'=>'category',
                                'type'=>'radio',
                                'id'=>'category',
                                'value'=>1,
                                'checked'=>$person_info->category == '1')
                                ); ?> <?php echo lang('counters_lang.counters_category_counter'); ?>
                    </label>
                    <label class="radio-inline">
                        <?php echo form_radio(array(
                                'name'=>'category',
                                'type'=>'radio',
                                'id'=>'category',
                                'value'=>3,
                                'checked'=>$person_info->category == '3')
                                ); ?> 
                                <?php echo lang('counters_lang.counters_category_pizza'); ?>
                    </label>
                    <label class="radio-inline">
                        <?php echo form_radio(array(
                                'name'=>'category',
                                'type'=>'radio',
                                'id'=>'category',
                                'value'=>2,
                                'checked'=>$person_info->category == '2')
                                ); ?> <?php echo lang('counters_lang.counters_category_production'); ?>
                    </label>
                </div>
            </div>

            <!-- <div class="form-group form-group-sm" id="production_category_display" <?php //echo empty($selected_production_category) ? 'style="display: none;"' : ''; ?>>
                <?php //echo form_label(lang('production_category'), 'production_category', array('class' => 'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php //echo form_dropdown('production_category', $production_categories, $selected_production_category, array('class' => 'form-control', 'id' => 'production_category')); ?>
                </div>
            </div> -->

            <div class="form-group form-group-sm">
                <?php echo form_label(lang('counters_lang.counters_company_name'), 'company_name', array('class'=>'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                        'name'=>'company_name',
                        'id'=>'company_name_input',
                        'class'=>'form-control input-sm',
                        'value'=>$person_info->company_name)
                        );?>
                </div>
            </div>
            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_first_name'), 'first_name', array('class'=>'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                            'name'=>'first_name',
                            'id'=>'first_name',
                            'class'=>'form-control input-sm',
                            'value'=>$person_info->first_name)
                            );?>
                </div>
            </div>

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_last_name'), 'last_name', array('class'=>'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                            'name'=>'last_name',
                            'id'=>'last_name',
                            'class'=>'form-control input-sm',
                            'value'=>$person_info->last_name)
                            );?>
                </div>
            </div>

            <div class="form-group form-group-sm" <?php echo ($person_info->category!=2) ? 'style="display: none;"' : ''; ?> id="pizza_filling_display">
                <?php echo form_label(lang('counters_lang.counters_pizza_filling'), 'special_counter', array('class' => 'control-label col-xs-3')); ?>
                <div class='col-xs-1'>
                    <?php echo form_checkbox(array(
                            'name' => 'special_counter',
                            'id' => 'special_counter',
                            'value' => 1,
                            'checked' => ($person_info->special_counter) ? 1 : 0)
                    ); ?>
                </div>
            </div>

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_gender'), 'gender', !empty($basic_version) ? array('class'=>'required control-label col-xs-3') : array('class'=>'control-label col-xs-3')); ?>
                <div class="col-xs-4">
                    <label class="radio-inline">
                        <?php echo form_radio(array(
                                'name'=>'gender',
                                'type'=>'radio',
                                'id'=>'gender',
                                'value'=>1,
                                'checked'=>$person_info->gender === '1')
                                ); ?> <?php echo lang('common_lang.common_gender_male'); ?>
                    </label>
                    <label class="radio-inline">
                        <?php echo form_radio(array(
                                'name'=>'gender',
                                'type'=>'radio',
                                'id'=>'gender',
                                'value'=>0,
                                'checked'=>$person_info->gender === '0')
                                ); ?> <?php echo lang('common_lang.common_gender_female'); ?>
                    </label>

                </div>
            </div>

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_email'), 'email', array('class'=>'control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <div class="input-group">
                        <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-envelope"></span></span>
                        <?php echo form_input(array(
                                'name'=>'email',
                                'id'=>'email',
                                'class'=>'form-control input-sm',
                                'value'=>$person_info->email)
                                );?>
                    </div>
                </div>
            </div>

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_phone_number'), 'phone_number', array('class'=>'control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <div class="input-group">
                        <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-phone-alt"></span></span>
                        <?php echo form_input(array(
                                'name'=>'phone_number',
                                'id'=>'phone_number',
                                'class'=>'form-control input-sm',
                                'value'=>$person_info->phone_number)
                                );?>
                    </div>
                </div>
            </div>

            <!-- <div class="form-group form-group-sm">
                <?php //echo form_label(lang('counter_can_print'), 'can_print', array('class' => 'control-label col-xs-3')); ?>
                <div class='col-xs-1'>
                    <?php //echo form_checkbox(array(
                            //'name' => 'can_print',
                            //'id' => 'can_print',
                            //'value' => 1,
                            //'checked' => ($person_info->can_print) ? 1 : 0)
                    //); ?>
                </div>
            </div> -->

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('common_lang.common_comments'), 'comments', array('class'=>'control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_textarea(array(
                            'name'=>'comments',
                            'id'=>'comments',
                            'class'=>'form-control input-sm',
                            'value'=>$person_info->comments)
                            );?>
                </div>
            </div>

        </fieldset>
    </div>

    <div class="tab-pane" id="counter_login_info">
        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('counters_lang.counters_username'), 'username', array('class' => 'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <div class="input-group">
                        <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-user"></span></span>
                        <?php echo form_input(array(
                                'name' => 'username',
                                'id' => 'username',
                                'class' => 'form-control input-sm',
                                'value' => $person_info->username)
                        ); ?>
                    </div>
                </div>
            </div>

            <?php $password_label_attributes = $person_info->person_id == "" ? array('class' => 'required') : array(); ?>

            <div class="form-group form-group-sm">
                <?php echo form_label(lang('counters_lang.counters_password'), 'password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
                <div class='col-xs-8'>
                    <div class="input-group">
                        <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-lock"></span></span>
                        <?php echo form_password(array(
                                'name' => 'password',
                                'id' => 'password',
                                'class' => 'form-control input-sm')
                        ); ?>
                    </div>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?php echo form_label(lang('counters_lang.counters_repeat_password'), 'repeat_password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
                <div class='col-xs-8'>
                    <div class="input-group">
                        <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-lock"></span></span>
                        <?php echo form_password(array(
                                'name' => 'repeat_password',
                                'id' => 'repeat_password',
                                'class' => 'form-control input-sm')
                        ); ?>
                    </div>
                </div>
            </div>

            <p><?php echo lang("warehouses_lang.warehouses_permission_desc"); ?></p>

            <ul id="permission_list">
                <?php
                foreach ($all_modules as $module) {
                    ?>
                    <li>
                        <?php echo form_checkbox("grants[]", $module->module_id, $module->grant, array('class'=>'module')); ?>
                        <?php //echo form_checkbox("grants[]", $module->module_id, $module->grant, array('class'=>'module', 'readonly'=>'readonly', 'onclick'=>'return false')); ?>
                        <span class="medium"><?php echo lang('module_lang.module_' . $module->module_id); ?>:</span>
                        <span
                            class="small"><?php echo lang('module_lang.module_' . $module->module_id . '_desc'); ?></span>
                        <?php
                        foreach ($all_subpermissions as $permission) {
                            $exploded_permission = explode('_', $permission->permission_id);
                            if ($permission->module_id == $module->module_id) {
                                $lang_key = $module->module_id . '_' . $exploded_permission[1] . (!empty($exploded_permission[2]) ? '_' . $exploded_permission[2] : '');
                                $lang_line = lang($lang_key);
                                $lang_line = (lang($lang_key) == $lang_line) ? $exploded_permission[1] : $lang_line;
                                if (!empty($lang_line)) {
                                    ?>
                                    <ul>
                                        <li>
                                            <?php echo form_checkbox("grants[]", $permission->permission_id, $permission->grant, array('class'=>'module')); ?>
                                            <?php //echo form_checkbox("grants[]", $permission->permission_id, $permission->grant, array('class'=>'module', 'readonly'=>'readonly', 'onclick'=>'return false')); ?>
                                            <span class="medium"><?php echo $lang_line ?></span>
                                        </li>
                                    </ul>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </fieldset>
    </div>

    <div class="tab-pane" id="counter_items_info">
        <div id="production_tab_pane" <?php echo (empty($data['selected_items']) && $person_info->category!=2) ? 'style="display: none;"' : ''; ?>>
            <style type="text/css">
                .select2-selection__rendered{
                    height: 100px !important;
                    overflow-y: auto !important;
                }
            </style>
            <fieldset style="height: 400px;">
                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('counters_lang.counters_item_type'), 'item_type', array('class'=>'control-label col-xs-3')); ?>
                    <div class="col-xs-8">
                        <label class="radio-inline">
                            <?php echo form_radio(array(
                                    'name'=>'item_type',
                                    'type'=>'radio',
                                    'id'=>'item_type',
                                    'value'=>1,
                                    'checked'=>'checked')
                                    ); ?> 
                            <?php echo lang('counters_lang.counters_item_pos'); ?>
                        </label>
                        <label class="radio-inline">
                            <?php echo form_radio(array(
                                    'name'=>'item_type',
                                    'type'=>'radio',
                                    'id'=>'item_type',
                                    'value'=>2,
                                    'checked'=>'')
                                    ); ?> 
                            <?php echo lang('counters_lang.counters_item_other'); ?>
                        </label>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('raw_order_lang.raw_orders_add_item'), 'item', array('class'=>'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <?php 
                            echo form_input(array(
                                'name'=>'item',
                                'id'=>'item',
                                'class'=>'form-control input-sm')
                            );
                        ?>
                    </div>
                </div>
                <ul id="item_error_message_box" class="error_message_box">
                    
                </ul>
                <!-- <div class="form-group form-group-sm">
                    <?php //echo form_label(lang('items_select'), 'item_ids', array('class'=>'required control-label col-xs-3')); ?>
                    <div class="col-xs-8">
                        <?php //echo form_multiselect('item_ids[]', $items, $selected_items, array('id' => 'item_ids', 'class' => 'form-control js-example-basic-multiple', 'multiple'=>'multiple')); ?>
                    </div>
                </div> -->
                <table id="category_items" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%"><?php echo lang('common_lang.common_delete'); ?></th>
                            <th width="30%"><?php echo lang('items_lang.items_category'); ?></th>
                            <th width="60%"><?php echo lang('items_lang.items_name'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($selected_items as $category_item)
                            {
                            ?>
                                <tr>
                                    <td><a href='#' onclick='return delete_item_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td>
                                    <td><?php echo $category_item['category']; ?></td>
                                    <td><?php echo $category_item['name']; ?>
                                        <input class="category_item form-control input-sm" id="category_item_<?php echo $category_item['item_id'] ?>" aria-required='true' aria-invalid='true' type='hidden' name="category_item[<?php echo $category_item['item_id'] ?>]" value="<?php echo $category_item['type'] ?>"/>
                                    </td>
                                </tr>
                            <?php
                            }
                        ?>
                    </tbody>
                </table>
            </fieldset>
        </div>
    </div>
</div>
<?php echo form_close(); ?>

<?php echo form_input(array('name'=>'item_type_selected','type'=>'hidden','id'=>'item_type_selected','value'=>1)); ?>

<script type="text/javascript">
    //validation and submit handling
    $(document).ready(function () {

        // $('.js-example-basic-multiple').select2({
        //     placeholder: 'Select Multipe Items',
        //     allowClear: true,
        //     closeOnSelect: false,
        //     enable: false,
        //     minimumResultsForSearch: Infinity
        // });
        
        //$('input:checkbox[name="pizza_filling"]').change(function(){
            // if (this.checked) {
            //     $('input[value="pizza_orders_list"]').prop("checked", true);
            //     $('input[value="reports_pizza_stock"]').prop("checked", true);
            // }else{
            //     $('input[value="pizza_orders_list"]').prop("checked", false);
            //     $('input[value="reports_pizza_stock"]').prop("checked", false);
            // }
        //});
        $('input:radio[name="item_type"]').change(function(){
            var type = $(this).val();
            $('#item_type_selected').val(type);
        });

        $('input:radio[name="category"]').change(function(){
            var category = $(this).val();
            if(category==1 || category==3){

                // remove rules
                // // $('#item_ids').rules("remove", "required");
                // // $("#item_ids").val("");

                // $('#production_category_display').fadeOut('slow');
                $('#item').rules("remove", "required");
                $('#pizza_filling').prop("checked", false);
                $('#pizza_filling_display').fadeOut('slow');
                $('#production_tab').fadeOut('slow');
                $('#production_tab_pane').fadeOut('slow');
                if(category==3){
                    // $('input[value="pizza_orders"]').prop("checked", true);
                    // $('input[value="pizza_orders_items"]').prop("checked", true);
                    // $('input[value="pizza_orders_status"]').prop("checked", true);
                    // $('input[value="reports_pizza_stock"]').prop("checked", true);
                }else{
                    // $('input[value="pizza_orders"]').prop("checked", false); 
                    // $('input[value="pizza_orders_items"]').prop("checked", false);
                    // $('input[value="pizza_orders_status"]').prop("checked", false);
                    // $('input[value="reports_pizza_stock"]').prop("checked", false); 
                }
            }else{
                // $('input[value="pizza_orders"]').prop("checked", false);
                // $('input[value="pizza_orders_items"]').prop("checked", false);
                // $('input[value="pizza_orders_status"]').prop("checked", false);
                // $('input[value="reports_pizza_stock"]').prop("checked", false);
                // add rules
                // // $("#item_ids").rules('add', {
                // //         required: true,
                // //         messages: {
                // //             required: "<?php //echo lang('counters_lang.counters_items_required'); ?>",
                // //        }
                // //    });

                // $('#production_category_display').fadeIn('slow');
                $("#item").rules('add', {
                    required: true,
                    messages: {
                        required: "<?php echo lang('raw_orders_items_required'); ?>",
                    }
                });
                $('#pizza_filling_display').fadeIn('slow');
                $('#production_tab').fadeIn('slow');
                $('#production_tab_pane').fadeIn('slow');
            }
        });

        $('#item').keypress(function(){
            
            $("#item").autocomplete({
                source: '<?php echo base_url(); ?>'+'counters/suggest/'+$('#item_type_selected').val(),
                minChars:0,
                autoFocus: false,
                delay:10,
                appendTo: ".modal-content",
                select: function(e, ui) {
                    
                    $('#item_error_message_box').html('');
                    $("#category_items tr").removeClass('error_message_box');

                    if ($("#category_item_" + ui.item.value).length == 1)
                    {
                        $("#category_item_" + ui.item.value).parent('td').parent('tr').addClass('error_message_box');
                        $('#item_error_message_box').html('<li><label class="has-error" style="display: inline-block;">Item already added to list.</label></li>');
                    }
                    else
                    {
                        $("#category_items").append("<tr><td><a href='#' onclick='return delete_item_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td><td>" + ui.item.category + "</td><td>" + ui.item.label + "<input class='category_item form-control input-sm' id='category_item_" + ui.item.value + "' aria-required='true' aria-invalid='true' type='hidden' name=category_item[" + ui.item.value + "] value='" + ui.item.type + "'/></td></tr>");

                        $('#item').rules("remove", "required");
                        
                    }

                    $("#item").val("");
                    return false;
                }
            });
        });

        $.validator.setDefaults({ignore: []});

        $('#counter_form').validate($.extend({
            submitHandler: function (form) {
                console.log('loading...');
                //var oldHtml = $('.bootstrap-dialog-footer').html();
                //console.log(oldHtml);
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?php echo base_url('counters'); ?>', response);
                        //console.log('done');
                    },
                    dataType: 'json'
                });
            },
            rules: {
                category:"required",
                first_name: "required",
                last_name: "required",
                company_name: "required",
                username: {
                    required: true,
                    minlength: 5
                },

                password: {
                    <?php
                    if($person_info->person_id == "")
                    {
                    ?>
                    required: true,
                    <?php
                    }
                    ?>
                    minlength: 8
                },
                repeat_password: {
                    equalTo: "#password"
                },
                email: "email"
            },
            messages: {
                category:"<?php echo lang('counters_lang.counters_category_required'); ?>",
                company_name:"<?php echo lang('counters_lang.counters_company_name_required'); ?>",
                first_name: "<?php echo lang('common_lang.common_first_name_required'); ?>",
                last_name: "<?php echo lang('common_lang.common_last_name_required'); ?>",
                username: {
                    required: "<?php echo lang('counters_lang.counters_username_required'); ?>",
                    minlength: "<?php echo lang('counters_lang.counters_username_minlength'); ?>"
                },

                password: {
                    <?php
                    if($person_info->person_id == "")
                    {
                    ?>
                    required: "<?php echo lang('counters_lang.counters_password_required'); ?>",
                    <?php
                    }
                    ?>
                    minlength: "<?php echo lang('counters_lang.counters_password_minlength'); ?>"
                },
                repeat_password: {
                    equalTo: "<?php echo lang('counters_lang.counters_password_must_match'); ?>"
                },
                email: "<?php echo lang('common_lang.common_email_invalid_format'); ?>"
            }
        }, form_support.error));
    });

    function delete_item_row(link)
    {
        $(link).parent().parent().remove();
        var i= 0;
        $('input.category_item').each(function () {
            i = i+1;
        });
        if(i<1){
            $("#item").rules('add', {
                required: true,
                messages: {
                    required: "<?php echo lang('raw_orders_items_required'); ?>",
                }
            });

        }
        return false;
    }

</script>