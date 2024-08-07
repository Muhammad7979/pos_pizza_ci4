<div id="required_fields_message">
    <?php echo lang('common_lang.common_fields_required_message'); ?>
</div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php if($person_info->person_id !== ''){

       echo form_open('employees/save/' . $person_info->person_id, array('id' => 'employee_form', 'class' => 'form-horizontal'));

        }else{
       echo form_open('employees/save', array('id' => 'employee_form', 'class' => 'form-horizontal'));

        }
        ?>

<ul class="nav nav-tabs nav-justified" data-tabs="tabs">
    <li class="active" role="presentation">
        <a data-toggle="tab"
           href="#employee_basic_info"><?php echo lang("employees_lang.employees_basic_information"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab" href="#employee_login_info"><?php echo lang("employees_lang.employees_login_info"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab"
           href="#employee_permission_info"><?php echo lang("employees_lang.employees_permission_info"); ?></a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade in active" id="employee_basic_info">
        <fieldset>
            <?php include(APPPATH . "Views/people/form_basic_info.php"); ?>
        </fieldset>
    </div>

    <div class="tab-pane" id="employee_login_info">
        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('employees_lang.employees_username'), 'username', array('class' => 'required control-label col-xs-3')); ?>
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
                <?php echo form_label(lang('employees_lang.employees_password'), 'password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
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
                <?php echo form_label(lang('employees_lang.employees_repeat_password'), 'repeat_password', array_merge($password_label_attributes, array('class' => 'control-label col-xs-3'))); ?>
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

    <div class="tab-pane" id="employee_permission_info">
        <fieldset>
            <p><?php echo lang("employees_lang.employees_permission_desc"); ?></p>

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
                                $lang_key = $module->module_id . '_' . $exploded_permission[1];
                                $lang_line = lang($lang_key);
                                $lang_line = (lang($lang_key) == $lang_line) ? $exploded_permission[1] : $lang_line;
                                // $lang_line = ($this->lang->line_tbd($lang_key) == $lang_line) ? $exploded_permission[1] : $lang_line;
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
        }, '<?php echo lang('employees_lang.employees_subpermission_required'); ?>');

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

        $('#employee_form').validate($.extend({
            submitHandler: function (form) {
                console.log('loading...');
                //var oldHtml = $('.bootstrap-dialog-footer').html();
                //console.log(oldHtml);
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?php echo base_url('employees'); ?>', response);
                        //console.log('done');
                    },
                    dataType: 'json'
                });
            },
            rules: {
                first_name: "required",
                last_name: "required",
                username: {
                    required: true,
                    minlength: 5
                },

                password: {
                    <?php if($person_info->person_id == ""){?> required: true, <?php } ?> minlength: 8, },
                repeat_password: {
                    equalTo: "#password"
                },
                email: "email"
            },
            messages: {
                first_name: "<?php echo lang('common_lang.common_first_name_required'); ?>",
                last_name: "<?php echo lang('common_lang.common_last_name_required'); ?>",
                username: {
                    required: "<?php echo lang('employees_lang.employees_username_required'); ?>",
                    minlength: "<?php echo lang('employees_lang.employees_username_minlength'); ?>"
                },

                password: {
                    <?php
                    if($person_info->person_id == "")
                    {
                    ?>
                    required: "<?php echo lang('employees_lang.employees_password_required'); ?>",
                    <?php
                    }
                    ?>
                    minlength: "<?php echo lang('employees_lang.employees_password_minlength'); ?>"
                },
                repeat_password: {
                    equalTo: "<?php echo lang('employees_lang.employees_password_must_match'); ?>"
                },
                email: "<?php echo lang('common_lang.common_email_invalid_format'); ?>"
            }
        }, form_support.error));
    });
</script>