<!-- app/Views/items/form_bulk.php -->
<div id="required_fields_message"><?= lang('items_lang.items_edit_fields_you_want_to_update'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open('items/bulk_update/', ['id' => 'item_form', 'class' => 'form-horizontal']); ?>
<fieldset id="bulk_item_basic_info">
    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_name'), 'name', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control input-sm']) ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_category'), 'category', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <div class="input-group">
                <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
                <?= form_input(['name' => 'category', 'id' => 'category', 'class' => 'form-control input-sm']) ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_supplier'), 'supplier', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_dropdown('supplier_id', $suppliers, '', ['class' => 'form-control']) ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_cost_price'), 'cost_price', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?php if (!currency_side()): ?>
                    <span class="input-group-addon input-sm"><b><?= $appData['currency_symbol'] ?></b></span>
                <?php endif; ?>
                <?= form_input(['name' => 'cost_price', 'id' => 'cost_price', 'class' => 'form-control input-sm']) ?>
                <?php if (currency_side()): ?>
                    <span class="input-group-addon input-sm"><b><?= $appData['currency_symbol'] ?></b></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group">
        <?= form_label(lang('items_lang.items_unit_price'), 'unit_price', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?php if (!currency_side()): ?>
                    <span class="input-group-addon input-sm"><b><?= $appData['currency_symbol'] ?></b></span>
                <?php endif; ?>
                <?= form_input(['name' => 'unit_price', 'id' => 'unit_price', 'class' => 'form-control input-sm']) ?>
                <?php if (currency_side()): ?>
                    <span class="input-group-addon input-sm"><b><?= $appData['currency_symbol'] ?></b></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_tax_1'), 'tax_percent_1', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-4">
            <?= form_input('tax_names[]', $appData['default_tax_1_name'], ['id' => 'tax_name_1', 'class' => 'form-control input-sm']) ?>
        </div>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?= form_input('tax_percents[]', ($appData['default_tax_1_rate']), ['id' => 'tax_percent_name_1', 'class' => 'form-control input-sm']) ?>
                <span class="input-group input-group-addon"><b>%</b></span>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_tax_2'), 'tax_percent_2', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-4">
            <?= form_input('tax_names[]', $appData['default_tax_2_name'], ['id' => 'tax_name_2', 'class' => 'form-control input-sm']) ?>
        </div>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?= form_input('tax_percents[]', ($appData['default_tax_2_rate']), ['id' => 'tax_percent_name_2', 'class' => 'form-control input-sm']) ?>
                <span class="input-group input-group-addon"><b>%</b></span>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_reorder_level'), 'reorder_level', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-4">
            <?= form_input(['name' => 'reorder_level', 'id' => 'reorder_level', 'class' => 'form-control input-sm']) ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_description'), 'description', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_textarea(['name' => 'description', 'id' => 'description', 'class' => 'form-control input-sm']) ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_allow_alt_description'), 'allow_alt_description', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_dropdown('allow_alt_description', $allow_alt_description_choices, '', ['class' => 'form-control']) ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_is_serialized'), 'is_serialized', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_dropdown('is_serialized', $serialization_choices, '', ['class' => 'form-control']) ?>
        </div>
    </div>

    <!-- CUSTOM 1 (Allow Discount) -->
    <div class="form-group form-group-sm">
        <?= form_label($appData['custom1_name'], 'custom1', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_dropdown('custom1', ['' => 'Choose', 'yes' => 'Yes', 'no' => 'No'], '', ['class' => 'form-control']) ?>
        </div>
    </div>

    <!-- CUSTOM 2 (Item Type) -->
    <div class="form-group form-group-sm">
        <?= form_label($appData['custom2_name'], 'custom2', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_dropdown('custom2', ['' => 'Choose', 'quantity' => 'Quantity', 'scale' => 'Scale', 'price' => 'Price'], '', ['class' => 'form-control']) ?>
        </div>
    </div>

    <?php if ($gu->isServer()): ?>
        <!-- CUSTOM 10 (Protected Item) -->
        <div class="form-group form-group-sm">
            <?= form_label($appData['custom10_name'], 'custom10', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('custom10', ['' => 'Choose', 'yes' => 'Yes', 'no' => 'No'], '', ['class' => 'form-control']) ?>
            </div>
        </div>
    <?php endif; ?>
</fieldset>

<?= form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
    $("#category").autocomplete({
        source: "<?= site_url('items/suggest_category'); ?>",
        appendTo: '.modal-content',
        delay: 10
    });

    var confirm_message = false;
    $("#tax_percent_name_2, #tax_name_2").prop('disabled', true);
    $("#tax_percent_name_1, #tax_name_1").blur(function () {
        var disabled = !($("#tax_percent_name_1").val() + $("#tax_name_1").val());
        $("#tax_percent_name_2, #tax_name_2").prop('disabled', disabled);
        confirm_message = disabled ? "" : "<?= lang('items_lang.items_confirm_bulk_edit_wipe_taxes') ?>";
    });

    $('#item_form').validate($.extend({
        submitHandler: function (form) {
            if (!confirm_message || confirm(confirm_message)) {
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    beforeSubmit: function (arr, $form, options) {
                        arr.push({name: 'item_ids', value: table_support.selected_ids().join(":")});
                    },
                    success: function (response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?= site_url('items'); ?>', response);
                    },
                    dataType: 'json'
                });
            }
        },
        rules: {
            unit_price: {
                number: true
            },
            tax_percent: {
                number: true
            },
            quantity: {
                number: true
            },
            reorder_level: {
                number: true
            }
        },
        messages: {
            unit_price: {
                number: "<?= lang('items_lang.items_unit_price_number'); ?>"
            },
            tax_percent: {
                number: "<?= lang('items_lang.items_tax_percent_number'); ?>"
            },
            quantity: {
                number: "<?= lang('items_lang.items_quantity_number'); ?>"
            },
            reorder_level: {
                number: "<?= lang('items_lang.items_reorder_level_number'); ?>"
            }
        }
    }, form_support.error));
});

</script>
