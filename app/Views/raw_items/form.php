<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php
/**
 * disable non editable raw_items for local
 */
if($item_info->custom10 != 'no' && $item_info->item_id != '' && !$gu->isServer()) {
    $style = "class='disabled' disabled";
    echo "<b style='color:red;'>This Item cannot be modified.</b> <hr/>";
    echo "<script>$('#submit').hide();</script>";
}
else {
    $style = "";
}
    ?>

    <?php echo form_open('raw_items/save/' . $item_info->item_id, array('id' => 'item_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>
    <fieldset id="item_basic_info" <?php echo $style; ?>>
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_item_number'), 'item_number', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
                    <?php echo form_input(array(
                            'name' => 'item_number',
                            'id' => 'item_number',
                            'class' => 'form-control input-sm',
                            'value' => $item_info->item_number)
                    ); ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_name'), 'name', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <?php echo form_input(array(
                        'name' => 'name',
                        'id' => 'name',
                        'class' => 'form-control input-sm',
                        'value' => $item_info->name)
                ); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_category'), 'category', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
                    <?php echo form_input(array(
                            'name' => 'category',
                            'id' => 'category',
                            'class' => 'form-control input-sm',
                            'value' => $item_info->category)
                    ); ?>
                </div>
            </div>
        </div>

        <!-- <div class="form-group form-group-sm">
            <?php //echo form_label(lang('raw_items_lang.raw_items_warehouse'), 'warehouse', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <?php //echo form_dropdown('warehouse_id', $warehouses, $selected_warehouse, array('class' => 'form-control')); ?>
            </div>
        </div> -->

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_cost_price'), 'cost_price', array('class' => 'required control-label col-xs-3')); ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (!currency_side()): ?>
                        <span
                                class="input-group-addon input-sm"><b><?php echo $appData['currency_symbol']; ?></b></span>
                    <?php endif; ?>
                    <?php echo form_input(array(
                            'name' => 'cost_price',
                            'id' => 'cost_price',
                            'class' => 'form-control input-sm',
                            'value' => to_currency_no_money($item_info->cost_price))
                    ); ?>
                    <?php if (currency_side()): ?>
                        <span
                                class="input-group-addon input-sm"><b><?php echo $appData['currency_symbol']; ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <?php
        foreach ($stock_locations as $key => $location_detail) {
            ?>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('raw_items_lang.raw_items_quantity') . ' ' . $location_detail['location_name'], 'quantity_' . $key, array('class' => 'required control-label col-xs-3')); ?>
                <div class='col-xs-4'>
                    <?php echo form_input(array(
                            'name' => 'quantity_' . $key,
                            'id' => 'quantity_' . $key,
                            'class' => 'required quantity form-control',
                            'value' => ($item_info->item_id) ? to_quantity_decimals($location_detail['quantity']) : to_quantity_decimals(999999))
                    ); ?>
                </div>
            </div>
            <?php
        }
        ?>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('items_lang.items_reorder_level'), 'reorder_level', array('class' => 'required control-label col-xs-3')); ?>
            <div class='col-xs-4'>
                <?php echo form_input(array(
                        'name' => 'reorder_level',
                        'id' => 'reorder_level',
                        'class' => 'form-control input-sm',
                        'value' => isset($item_info->item_id) ? to_quantity_decimals($item_info->reorder_level) : to_quantity_decimals(0))
                ); ?>
            </div>
        </div>

        <!--    CUSTOM 2 (Item Type) -->
        <div class="form-group form-group-sm">
            <?php echo form_label($appData['custom2_name'], 'custom2', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <?php echo form_dropdown('custom2', [''=>'Choose','quantity'=>'Quantity','scale'=>'Scale', 'price'=>'Price'],
                            isset($item_info->custom2) ? $item_info->custom2 : '', array('class' => 'form-control')
                        ); ?>
            </div>
        </div>
        
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_description'), 'description', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <?php echo form_textarea(array(
                        'name' => 'description',
                        'id' => 'description',
                        'class' => 'form-control input-sm',
                        'value' => $item_info->description)
                ); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_image'), 'raw_items_image', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <div class="fileinput <?php echo $logo_exists ? 'fileinput-exists' : 'fileinput-new'; ?>"
                     data-provides="fileinput">
                    <div class="fileinput-new thumbnail" style="width: 100px; height: 100px;"></div>
                    <div class="fileinput-preview fileinput-exists thumbnail"
                         style="max-width: 100px; max-height: 100px;">
                        <img data-src="holder.js/100%x100%" alt="<?php echo lang('raw_items_lang.raw_items_image'); ?>"
                             src="<?php echo $image_path; ?>"
                             style="max-height: 100%; max-width: 100%;">
                    </div>
                    <div>
                        <span class="btn btn-default btn-sm btn-file">
                            <span class="fileinput-new"><?php echo lang("raw_items_lang.raw_items_select_image"); ?></span>
                            <span class="fileinput-exists"><?php echo lang("raw_items_lang.raw_items_change_image"); ?></span>
                            <input type="file" name="item_image" accept="image/*">
                        </span>
                        <a href="#" class="btn btn-default btn-sm fileinput-exists"
                           data-dismiss="fileinput"><?php echo lang("raw_items_lang.raw_items_remove_image"); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_allow_alt_description'), 'allow_alt_description', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-1'>
                <?php echo form_checkbox(array(
                        'name' => 'allow_alt_description',
                        'id' => 'allow_alt_description',
                        'value' => 1,
                        'checked' => ($item_info->allow_alt_description) ? true : false)
                ); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('raw_items_lang.raw_items_is_deleted'), 'is_deleted', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-1'>
                <?php echo form_checkbox(array(
                        'name' => 'is_deleted',
                        'id' => 'is_deleted',
                        'value' => 1,
                        'checked' => ($item_info->deleted) ? true : false)
                ); ?>
            </div>
        </div>

        

         <?php if($gu->isServer()): ?>
        <!--    CUSTOM 10 (Protected Item) -->
        <div class="form-group form-group-sm">
            <?php echo form_label($appData['custom10_name'], 'custom10', array('class' => 'control-label col-xs-3')); ?>
            <div class='col-xs-8'>
                <?php echo form_dropdown('custom10', [''=>'Choose','yes'=>'Yes','no'=>'No'],
                    '', array('class' => 'form-control')
                ); ?>
            </div>
        </div>
        <?php endif; ?>
    
    </fieldset>
    <?php echo form_close(); ?>

<script type="text/javascript">
    //validation and submit handling
    $(document).ready(function () {
        $("#new").click(function () {
            stay_open = true;
            $("#item_form").submit();
        });

        $("#submit").click(function () {
            stay_open = false;
        });

        var no_op = function (event, data, formatted) {
        };
        $("#category").autocomplete({
            source: "<?php echo site_url('raw_items/suggest_category');?>",
            delay: 10,
            appendTo: '.modal-content'
        });

        <?php for ($i = 1; $i <= 10; ++$i)
        {
        ?>
        $("#custom" +<?php echo $i; ?>).autocomplete({
            source: function (request, response) {
                $.ajax({
                    type: "POST",
                    url: "<?php echo site_url('raw_items/suggest_custom');?>",
                    dataType: "json",
                    data: $.extend(request, $extend(csrf_form_base(), {field_no: <?php echo $i; ?>})),
                    success: function (data) {
                        response($.map(data, function (item) {
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
        <?php
        }
        ?>

        $("a.fileinput-exists").click(function () {
            $.ajax({
                type: "GET",
                url: "<?php echo site_url("$controller_name/remove_logo/$item_info->item_id"); ?>",
                dataType: "json"
            })
        });

        $('#item_form').validate($.extend({
            submitHandler: function (form, event) {
                $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
                $('#submit').hide();
                $(form).ajaxSubmit({
                    success: function (response) {
                        var stay_open = dialog_support.clicked_id() != 'submit';
                        if (stay_open) {
                            // set action of item_form to url without item id, so a new one can be created
                            $("#item_form").attr("action", "<?php echo site_url("raw_items/save/")?>");
                            // use a whitelist of fields to minimize unintended side effects
                            $(':text, :input, :file, #description, #item_form').not('.quantity, #reorder_level,' +'#cost_price, #unit_price').val('');
                            // de-select any checkboxes, radios and drop-down menus
                            $(':input', '#item_form').not('#item_category_id').removeAttr('checked').removeAttr('selected');
                        }
                        else {
                            dialog_support.hide();
                        }
                        $('.bootstrap-dialog-footer .guLoader30px').remove();
                        table_support.handle_submit('<?php echo site_url('raw_items'); ?>', response, stay_open);
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
                        url: "<?php echo site_url($controller_name . '/check_item_number')?>",
                        type: "post",
                        data: $.extend(csrf_form_base(),
                            {
                                "item_id": "<?php echo $item_info->item_id; ?>",
                                "item_number": function () {
                                    return $("#item_number").val();
                                },
                            })
                    }
                },
                cost_price: {
                    required: true,
                    remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
                },
        <?php
        foreach($stock_locations as $key=>$location_detail)
        {
        ?>
        <?php echo 'quantity_' . $key ?>:
        {
            required:true,
                remote
        :
            "<?php echo site_url($controller_name . '/check_numeric')?>"
        }
        ,
        <?php
        }
        ?>
    },

        messages:
        {
            name:"<?php echo lang('raw_items_lang.raw_items_name_required'); ?>",
            item_number:"<?php echo lang('raw_items_lang.raw_items_item_number_duplicate'); ?>",
            category:"<?php echo lang('raw_items_lang.raw_items_category_required'); ?>",
            cost_price:
            {
                required:"<?php echo lang('raw_items_lang.raw_items_cost_price_required'); ?>",
                number:"<?php echo lang('raw_items_lang.raw_items_cost_price_number'); ?>"
            },
            <?php
            foreach($stock_locations as $key=>$location_detail)
            {
            ?>
            <?php echo 'quantity_' . $key ?>:
            {
                required:"<?php echo lang('raw_items_lang.raw_items_quantity_required'); ?>",
                number:"<?php echo lang('raw_items_lang.raw_items_quantity_number'); ?>"
            },
            <?php
            }
            ?>
        }
    },
        form_support.error
        ))
        ;
    });
</script>

