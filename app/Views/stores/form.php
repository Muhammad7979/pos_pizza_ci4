<div id="required_fields_message">
    <?php echo lang('common_lang.common_fields_required_message'); ?>
</div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('stores/save/' . $person_info->person_id, array('id' => 'store_form', 'class' => 'form-horizontal')); ?>
<ul class="nav nav-tabs nav-justified" data-tabs="tabs">
    <li class="active" role="presentation">
        <a data-toggle="tab"
           href="#store_basic_info"><?php echo lang("stores_lang.stores_basic_information"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab" href="#store_login_info"><?php echo lang("stores_lang.stores_login_info"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab"
           href="#store_permission_info"><?php echo lang("stores_lang.stores_permission_info"); ?></a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade in active" id="store_basic_info">
        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('stores_lang.stores_company_name'), 'company_name', array('class'=>'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                        'name'=>'company_name',
                        'id'=>'company_name_input',
                        'class'=>'form-control input-sm',
                        'value'=>$person_info->company_name)
                        );?>
                </div>
            </div>
            <?php echo view("people/form_basic_info"); ?>
            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('stores_lang.stores_account_number'), 'account_number', array('class'=>'control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                        'name'=>'account_number',
                        'id'=>'account_number',
                        'class'=>'form-control input-sm',
                        'value'=>$person_info->account_number)
                        );?>
                </div>
            </div>
        </fieldset>
    </div>

    <div class="tab-pane" id="store_login_info">
        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('stores_lang.stores_username'), 'username', array('class' => 'required control-label col-xs-3')); ?>
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
                <?php echo form_label(lang('stores_lang.stores_password'), 'password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
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
                <?php echo form_label(lang('stores_lang.stores_repeat_password'), 'repeat_password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
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
        </fieldset>
    </div>

    <div class="tab-pane" id="store_permission_info">
        <fieldset>
            <p><?php echo lang("stores_lang.stores_permission_desc"); ?></p>

            <ul id="permission_list">
                <?php
                foreach ($all_modules as $module) {
                    ?>
                    <li>
                        <?php echo form_checkbox("grants[]", $module->module_id, $module->grant, "class='module'"); ?>
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
                                            <?php echo form_checkbox("grants[]", $permission->permission_id, $permission->grant); ?>
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
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    //validation and submit handling
    $(document).ready(function () {
        $.validator.setDefaults({ignore: []});

        $.validator.addMethod("module", function (value, element) {
            var result = $("#permission_list input").is(":checked");
            $(".module").each(function (index, element) {
                var parent = $(element).parent();
                var checked = $(element).is(":checked");
                if ($("ul", parent).length > 0 && result) {
                    result &= !checked || (checked && $("ul > li > input:checked", parent).length > 0);
                }
            });
            return result;
        }, '<?php echo lang('stores_lang.stores_subpermission_required'); ?>');

        $("ul#permission_list > li > input[name='grants[]']").each(function () {
            var $this = $(this);
            $("ul > li > input", $this.parent()).each(function () {
                var $that = $(this);
                var updateCheckboxes = function (checked) {
                    $that.prop("disabled", !checked);
                    !checked && $that.prop("checked", false);
                };
                $this.change(function () {
                    updateCheckboxes($this.is(":checked"));
                });
                updateCheckboxes($this.is(":checked"));
            });
        });

        $('#store_form').validate($.extend({
            submitHandler: function (form) {
                console.log('loading...');
                //var oldHtml = $('.bootstrap-dialog-footer').html();
                //console.log(oldHtml);
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?php echo base_url('stores'); ?>', response);
                        //console.log('done');
                    },
                    dataType: 'json'
                });
            },
            rules: {
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
                first_name: "<?php echo lang('common_lang.common_first_name_required'); ?>",
                last_name: "<?php echo lang('common_lang.common_last_name_required'); ?>",
                company_name: "<?php echo lang('stores_lang.stores_company_name_required'); ?>",
                username: {
                    required: "<?php echo lang('stores_lang.stores_username_required'); ?>",
                    minlength: "<?php echo lang('stores_lang.stores_username_minlength'); ?>"
                },

                password: {
                    <?php
                    if($person_info->person_id == "")
                    {
                    ?>
                    required: "<?php echo lang('stores_lang.stores_password_required'); ?>",
                    <?php
                    }
                    ?>
                    minlength: "<?php echo lang('stores_lang.stores_password_minlength'); ?>"
                },
                repeat_password: {
                    equalTo: "<?php echo lang('stores_lang.stores_password_must_match'); ?>"
                },
                email: "<?php echo lang('common_lang.common_email_invalid_format'); ?>"
            }
        }, form_support.error));
    });
</script>