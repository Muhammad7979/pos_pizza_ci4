<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open_multipart('items/do_excel_import_update_prices/', array('id' => 'excel_form', 'class' => 'form-horizontal')); ?>
<fieldset id="item_basic_info">

    <div class="form-group form-group-sm">
        <?php echo form_label('Price Import Type', 'import_type', array('class' => 'control-label col-xs-4')); ?>
        <div class='col-xs-7'>
            <?php echo form_dropdown('import_type', ['default' => 'Default'], '', array('class' => 'form-control')); ?>
        </div>
    </div>


    <div class="form-group form-group-sm">
        <div class="col-xs-12">
            Select CSV to Update Prices of Items
        </div>
    </div>

    <div class="form-group form-group-sm">
        <div class='col-xs-12'>
            <div class="fileinput fileinput-new input-group" data-provides="fileinput">
                <div class="form-control" data-trigger="fileinput"><i
                        class="glyphicon glyphicon-file fileinput-exists"></i><span class="fileinput-filename"></span>
                </div>
                <span class="input-group-addon input-sm btn btn-default btn-file"><span
                        class="fileinput-new"><?php echo lang("common_lang.common_import_select_file"); ?></span><span
                        class="fileinput-exists"><?php echo lang("common_lang.common_import_change_file"); ?></span><input
                        type="file" id="file_path" name="file_path" accept=".csv"></span>
                <a href="#" class="input-group-addon input-sm btn btn-default fileinput-exists"
                   data-dismiss="fileinput"><?php echo lang("common_lang.common_import_remove_file"); ?></a>
            </div>
        </div>
    </div>
</fieldset>
<?php echo form_close(); ?>

<script type="text/javascript">
    //validation and submit handling
    $(document).ready(function () {
        $('#excel_form').validate($.extend({
            submitHandler: function (form) {
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        $.notify(response.message, {type: response.success ? 'success' : 'danger'});
                        table_support.refresh();

                    },
                    dataType: 'json'
                });
            },
            errorLabelContainer: "#error_message_box",
            wrapper: "li",
            rules: {
                file_path: "required"
            },
            messages: {
                file_path: "<?php echo lang('common_lang.common_import_full_path'); ?>"
            }
        }, form_support.error));
    });
</script>
