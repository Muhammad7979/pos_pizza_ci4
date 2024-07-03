<div id="required_fields_message"><?= lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>
<?= form_open('items/save_inventory/' . $item_info->item_id, ['id' => 'item_form', 'class' => 'form-horizontal']); ?>
    <fieldset id="inv_item_basic_info">
        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_item_number'), 'name', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-8">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
                    <?= form_input('item_number', isset($item_info->item_number) ? $item_info->item_number : '', ['id' => 'item_number', 'class' => 'form-control input-sm', 'disabled' => '']); ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_name'), 'name', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-8">
                <?= form_input('name', $item_info->name, ['id' => 'name', 'class' => 'form-control input-sm', 'disabled' => '']); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_category'), 'category', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-8">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
                    <?= form_input('category', $item_info->category, ['id' => 'category', 'class' => 'form-control input-sm', 'disabled' => '']); ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_stock_location'), 'stock_location', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-8">
                <?= form_dropdown('stock_location', $stock_locations, current($stock_locations), ['onchange' => 'fill_quantity(this.value)', 'class' => 'form-control']); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_current_quantity'), 'quantity', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-4">
                <?= form_input('quantity', to_quantity_decimals(current($item_quantities)) ?? '', ['id' => 'quantity', 'class' => 'form-control input-sm', 'disabled' => '']); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_add_minus'), 'quantity', ['class' => 'required control-label col-xs-3']); ?>
            <div class="col-xs-4">
                <?= form_input('newquantity', '', ['id' => 'newquantity', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_inventory_comments'), 'description', ['class' => 'control-label col-xs-3']); ?>
            <div class="col-xs-8">
                <?= form_textarea('trans_comment', '', ['id' => 'trans_comment', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>
    </fieldset>
<?= form_close(); ?>

<script type="text/javascript">
    // validation and submit handling
    $(document).ready(function () {
        $('#item_form').validate({
            submitHandler: function (form) {
                $(form).ajaxSubmit({
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?= site_url('items'); ?>', response);
                    },
                    dataType: 'json'
                });
            },
            errorLabelContainer: "#error_message_box",
            wrapper: "li",
            rules: {
                newquantity: {
                    required: true,
                    number: true
                }
            },
            messages: {
                newquantity: {
                    required: "<?= lang('items_lang.items_quantity_required'); ?>",
                    number: "<?= lang('items_lang.items_quantity_number'); ?>"
                }
            }
        });
    });

    function fill_quantity(val) {
        var item_quantities = <?= json_encode($item_quantities); ?>;
        document.getElementById("quantity").value = parseFloat(item_quantities[val]).toFixed(<?= quantity_decimals(); ?>);
    }
</script>
