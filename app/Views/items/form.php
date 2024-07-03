<div id="required_fields_message"><?= lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php
/**
 * Disable non-editable items for local
 */
if ($item_info->custom10 != 'no' && $item_info->item_id != '' && !$gu->isServer()) {
    $style = "class='disabled' disabled";
    echo "<b style='color:red;'>This Item cannot be modified.</b> <hr/>";
} else {
    $style = "";
}
?>

<?= form_open('items/save' . $item_info->item_id, ['id' => 'item_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']); ?>
<fieldset id="item_basic_info" <?= $style; ?>>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_item_number'), 'item_number', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-8'>
            <div class="input-group">
                <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
                <?= form_input('item_number',( isset($item_info->item_number) ? $item_info->item_number : ''), ['id' => 'item_number', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_name'), 'name', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-8'>
            <div class="input-group">
                <?= form_input('name', $item_info->name, ['id' => 'name', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_category'), 'category', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-8'>
            <div class="input-group">
                <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
                <?= form_input('category', $item_info->category, ['id' => 'category', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_supplier'), 'supplier', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-8'>
            <div class="input-group">
                <?= form_dropdown('supplier_id', $suppliers, $selected_supplier, ['class' => 'form-control']); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_cost_price'), 'cost_price', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-4'>
            <div class="input-group input-group-sm">
                <?php if (!currency_side()) : ?>
                    <span class="input-group-addon input-sm"><b><?= esc($appData['currency_symbol']); ?></b></span>
                <?php endif; ?>
                <?= form_input('cost_price', to_currency_no_money((float) $item_info->cost_price), [
                    'id' => 'cost_price',
                    'class' => 'form-control input-sm',
                ]); ?>
                <?php if (currency_side()) : ?>
                    <span class="input-group-addon input-sm"><b><?= esc($appData['currency_symbol']); ?></b></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_unit_price'), 'unit_price', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-4'>
            <div class="input-group input-group-sm">
                <?php if (!currency_side()) : ?>
                    <span class="input-group-addon input-sm"><b><?= esc($appData['currency_symbol']); ?></b></span>
                <?php endif; ?>
                <?= form_input('unit_price', to_currency_no_money((float) $item_info->unit_price), [
                    'id' => 'unit_price',
                    'class' => 'form-control input-sm',
                ]); ?>
                <?php if (currency_side()) : ?>
                    <span class="input-group-addon input-sm"><b><?= esc($appData['currency_symbol']); ?></b></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_tax_1'), 'tax_percent_1', ['class' => 'control-label col-xs-3']); ?>
        <div class="col-xs-4">
            <?= form_input('tax_names[]', isset($item_tax_info[0]['name']) ? $item_tax_info[0]['name'] : esc($appData['default_tax_1_name']), ['id' => 'tax_name_1', 'class' => 'form-control input-sm']); ?>
        </div>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?= form_input('tax_percents[]', isset($item_tax_info[0]['percent']) ? to_tax_decimals($item_tax_info[0]['percent']) : to_tax_decimals($default_tax_1_rate), ['id' => 'tax_percent_name_1', 'class' => 'form-control input-sm']); ?>
                <span class="input-group-addon input-sm"><b>%</b></span>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_tax_2'), 'tax_percent_2', ['class' => 'control-label col-xs-3']); ?>
        <div class="col-xs-4">
            <?= form_input('tax_names[]', isset($item_tax_info[1]['name']) ? $item_tax_info[1]['name'] : esc($appData['default_tax_2_name']), ['id' => 'tax_name_2', 'class' => 'form-control input-sm']); ?>
        </div>
        <div class="col-xs-4">
            <div class="input-group input-group-sm">
                <?= form_input('tax_percents[]', isset($item_tax_info[1]['percent']) ? to_tax_decimals($item_tax_info[1]['percent']) : to_tax_decimals($default_tax_2_rate), ['id' => 'tax_percent_name_2', 'class' => 'form-control input-sm']); ?>
                <span class="input-group-addon input-sm"><b>%</b></span>
            </div>
        </div>
    </div>



    <?php foreach ($stock_locations as $key => $location_detail) : ?>
        <div class="form-group form-group-sm">
            <?= form_label(lang('items_lang.items_quantity') . ' ' . $location_detail['location_name'], 'quantity_' . $key, ['class' => 'required control-label col-xs-3']) ?>
            <div class='col-xs-4'>
                <?= form_input('quantity_' . $key, isset($item_info->item_id) ? to_quantity_decimals($location_detail['quantity']) : to_quantity_decimals(0), ['id' => 'quantity_' . $key, 'class' => 'required quantity form-control']); ?>
            </div>
        </div>
    <?php endforeach; ?>


    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_receiving_quantity'), 'receiving_quantity', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-4'>
            <?= form_input('receiving_quantity', isset($item_info->item_id) ? to_quantity_decimals($item_info->receiving_quantity) : to_quantity_decimals(0), ['id' => 'receiving_quantity', 'class' => 'required form-control input-sm']); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_reorder_level'), 'reorder_level', ['class' => 'required control-label col-xs-3']); ?>
        <div class='col-xs-4'>
            <?= form_input('reorder_level', isset($item_info->item_id) ? to_quantity_decimals($item_info->reorder_level) : to_quantity_decimals(0), ['id' => 'reorder_level', 'class' => 'required form-control input-sm']); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_description'), 'description', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-8'>
            <div class="input-group">
                <?= form_textarea('description', $item_info->description, ['id' => 'description', 'class' => 'form-control input-sm']); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_image'), 'items_image', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <div class="fileinput <?= $logo_exists ? 'fileinput-exists' : 'fileinput-new' ?>" data-provides="fileinput">
                <div class="fileinput-new thumbnail" style="width: 100px; height: 100px;"></div>
                <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 100px; max-height: 100px;">
                    <!-- <img data-src="holder.js/100%x100%" alt="<?= lang('items_lang.items_image') ?>" src="<?= $image_path ?>" style="max-height: 100%; max-width: 100%;"> -->
                    <?php
                     if(isset($item_info->pic_id))
                    echo '<img width="50" height="50" src="'.base_url('uploads/item_pics/'.$item_info->pic_id).'"/>';
                    ?>
                   
                    
                </div>
                <div>
                    <span class="btn btn-default btn-sm btn-file">
                        <span class="fileinput-new"><?= lang('items_lang.items_select_image') ?></span>
                        <span class="fileinput-exists"><?= lang('items_lang.items_change_image') ?></span>
                        <input type="file" name="item_image" accept="image/*">
                    </span>
                    <a href="#" class="btn btn-default btn-sm fileinput-exists" data-dismiss="fileinput">
                        <?= lang('items_lang.items_remove_image') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>



    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_allow_alt_description'), 'allow_alt_description', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-1'>
            <?= form_checkbox('allow_alt_description', '1', ($item_info->allow_alt_description) ? 1 : 0, ['id' => 'allow_alt_description']); ?>
        </div>
    </div>


    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_is_serialized'), 'is_serialized', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-1'>
            <?= form_checkbox('is_serialized', '1', ($item_info->is_serialized) ? 1 : 0, ['id' => 'is_serialized']); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('items_lang.items_is_deleted'), 'is_deleted', ['class' => 'control-label col-xs-3']); ?>
        <div class='col-xs-1'>
            <?= form_checkbox('is_deleted', '1', ($item_info->deleted) ? 1 : 0, ['id' => 'is_deleted']); ?>
        </div>
    </div>

    <?php
    for ($i = 1; $i <= 10; ++$i) {
        if (!empty((esc($appData['custom' . $i . '_name'])))) {
            $itemArr = (array)$item_info;
            $style = $gu->isServer() || $i != 10 ? 'block' : 'none';
            $protected = $item_info->item_id != '' ? $itemArr['custom' . $i] : 'no';
    ?>

            <div class="form-group form-group-sm" style="display: <?= $style; ?>">
                <?= form_label(esc($appData['custom' . $i . '_name']), 'custom' . $i, ['class' => 'control-label col-xs-3']); ?>
                <div class="col-xs-8">
                    <?php if ($i == 1) : ?>
                        <?= form_dropdown('custom' . $i, ['yes' => 'Yes', 'no' => 'No'], $itemArr['custom' . $i], ['class' => 'form-control']); ?>
                    <?php elseif ($i == 2) : ?>
                        <?= form_dropdown('custom' . $i, ['', 'quantity' => 'Quantity', 'scale' => 'Scale', 'price' => 'Price'], $itemArr['custom' . $i], ['class' => 'form-control']); ?>
                    <?php elseif ($i == 10) : ?>
                        <?= form_dropdown('custom' . $i, ['', 'yes' => 'Yes', 'no' => 'No'], $protected, ['class' => 'form-control']); ?>
                    <?php else : ?>
                        <?= form_input('custom' . $i, $itemArr['custom' . $i], ['id' => 'custom' . $i, 'class' => 'form-control input-sm']); ?>
                    <?php endif; ?>
                </div>
            </div>

    <?php
        }
    }
    ?>


</fieldset>
<?= form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#new").click(function() {
            stay_open = true;
            $("#item_form").submit();
        });

        $("#submit").click(function() {
            stay_open = false;
        });

        var no_op = function(event, data, formatted) {};

        $("#category").autocomplete({
            source: "<?= site_url('items/suggest_category'); ?>",
            delay: 10,
            appendTo: '.modal-content'
        });

        <?php for ($i = 1; $i <= 10; ++$i) : ?>
            $("#custom<?= $i; ?>").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        type: "POST",
                        url: "<?= site_url('items/suggest_custom'); ?>",
                        dataType: "json",
                        data: $.extend(request, $extend(csrf_form_base(), {
                            field_no: <?= $i; ?>
                        })),
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    value: item.label
                                };
                            }))
                        }
                    });
                },
                delay: 10,
                appendTo: '.modal-content'
            });
        <?php endfor; ?>

        $("a.fileinput-exists").click(function() {
            $.ajax({
                type: "GET",
                url: "<?= site_url("$controller_name/remove_logo/$item_info->item_id/$item_info->pic_id"); ?>",
                dataType: "json"
            })
        });

        $('#item_form').validate($.extend({
            submitHandler: function(form, event) {
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function(response) {
                        var stay_open = dialog_support.clicked_id() != 'submit';
                        if (stay_open) {
                            // set action of item_form to url without item id, so a new one can be created
                            $("#item_form").attr("action", "<?= site_url("items/save/") ?>");
                            // use a whitelist of fields to minimize unintended side effects
                            $(':text, :password, :file, #description, #item_form').not('.quantity, #reorder_level, #tax_name_1,' +
                                '#tax_percent_name_1, #reference_number, #name, #cost_price, #unit_price, #taxed_cost_price, #taxed_unit_price').val('');
                            // de-select any checkboxes, radios and drop-down menus
                            $(':input', '#item_form').not('#item_category_id').removeAttr('checked').removeAttr('selected');
                        } else {
                            dialog_support.hide();
                        }
                        table_support.handle_submit('<?= site_url('items'); ?>', response, stay_open);
                    },
                    dataType: 'json'
                });
            },
            rules: {
                name: "required",
                category: "required",
                item_number: {
                    required: false,
                    remote: {
                        url: "<?= site_url($controller_name . '/check_item_number') ?>",
                        type: "post",
                        data: $.extend(csrf_form_base(), {
                            "item_id": "<?= $item_info->item_id; ?>",
                            "item_number": function() {
                                return $("#item_number").val();
                            }
                        })
                    }
                },
                cost_price: {
                    required: true,
                    remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                },
                unit_price: {
                    required: true,
                    remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                },
                /* <?php foreach ($stock_locations as $key => $location_detail) : ?>
                    <?= 'quantity_' . $key ?>: {
                        required: true,
                        remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                    },
                <?php endforeach; ?> */
                receiving_quantity: {
                    required: true,
                    remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                },
                reorder_level: {
                    required: true,
                    remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                },
                tax_percent: {
                    required: true,
                    remote: "<?= site_url($controller_name . '/check_numeric') ?>"
                }
            },
            messages: {
                name: "<?= lang('items_lang.items_name_required'); ?>",
                item_number: "<?= lang('items_lang.items_item_number_duplicate'); ?>",
                category: "<?= lang('items_lang.items_category_required'); ?>",
                cost_price: {
                    required: "<?= lang('items_lang.items_cost_price_required'); ?>",
                    number: "<?= lang('items_lang.items_cost_price_number'); ?>"
                },
                unit_price: {
                    required: "<?= lang('items_lang.items_unit_price_required'); ?>",
                    number: "<?= lang('items_lang.items_unit_price_number'); ?>"
                },
                /* <?php foreach ($stock_locations as $key => $location_detail) : ?>
                    <?= 'quantity_' . $key ?>: {
                        required: "<?= lang('items_lang.items_quantity_required'); ?>",
                        number: "<?= lang('items_lang.items_quantity_number'); ?>"
                    },
                <?php endforeach; ?> */
                receiving_quantity: {
                    required: "<?= lang('items_lang.items_quantity_required'); ?>",
                    number: "<?= lang('items_lang.items_quantity_number'); ?>"
                },
                reorder_level: {
                    required: "<?= lang('items_lang.items_reorder_level_required'); ?>",
                    number: "<?= lang('items_lang.items_reorder_level_number'); ?>"
                },
                tax_percent: {
                    required: "<?= lang('items_lang.items_tax_percent_required'); ?>",
                    number: "<?= lang('items_lang.items_tax_percent_number'); ?>"
                }
            }
        }, form_support.error));
    });
</script>