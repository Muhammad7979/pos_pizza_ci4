<div id="required_fields_message">
    <?php echo lang('common_lang.common_fields_required_message'); ?>
</div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('employees/save_biometric/' . $person_info->person_id, array('id' => 'employee_form', 'class' => 'form-horizontal')); ?>

    <fieldset>
        
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('common_lang.common_id'), 'id', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-user"></span></span>
                    <?php echo form_input(array(
                            'name' => 'id',
                            'id' => 'id',
                            'readonly' => true,
                            'class' => 'form-control input-sm',
                            'value' => $person_info->person_id)
                    ); ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label($this->lang->line('employees_username'), 'username', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-user"></span></span>
                    <?php echo form_input(array(
                            'name' => 'username',
                            'id' => 'username',
                            'readonly' => true,
                            'class' => 'form-control input-sm',
                            'value' => $person_info->username)
                    ); ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <div class='col-xs-12'>
                <div class="text-center">
                    <input type="button" class="btn btn-info" name="biometricCapture" value="Biometric Capture" onclick="captureBiometric('Register')">
                </div>
            </div>
        </div>
        <div class='col-xs-12'>
            <div class="text-center">
                <textarea name="templateXML" id="templateXML" required style="opacity: 0; height: 0;"></textarea>
                <label id="serverResult"></label>
            </div>
        </div>

    </fieldset>

<?php echo form_close(); ?>

<script type="text/javascript">
    //validation and submit handling
    $(document).ready(function () {
        
        $('#employee_form').validate($.extend({
            submitHandler: function (form) {
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?php echo base_url('employees'); ?>', response);
                    },
                    dataType: 'json'
                });
            },
            rules: {
                id: "required",
                username: {
                    required: true,
                    minlength: 5
                },
                templateXML: "required"
            },
            messages: {
                id: "<?php echo lang('common_lang.common_id_required'); ?>",
                username: {
                    required: "<?php echo $this->lang->line('employees_username_required'); ?>",
                    minlength: "<?php echo $this->lang->line('employees_username_minlength'); ?>"
                },
                templateXML: "Fingerprint is required"
            }
        }, form_support.error));
    });
</script>