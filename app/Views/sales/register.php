<?php
 include(APPPATH . 'Views/partial/header.php'); ?>

<?php
if (isset($error)) {
    echo "<div class='alert alert-dismissible alert-danger print_hide'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $error . "</div>";
}

if (!empty($warning)) {
    echo "<div class='alert alert-dismissible alert-warning print_hide'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $warning . "</div>";
}

if (isset($success)) {
    echo "<div class='alert alert-dismissible alert-success print_hide'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $success . "</div>";
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    if ($message) {
        echo "<div class='alert alert-dismissible alert-warning print_hide'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $message . "</div>";
    }
}



?>

<style>
    #buttons_sale,
    #payment_details,
    #suspended_sales_table,
    .sales_table_100 {
        margin: 14px 21px;
        width: auto
    }

    section#body .payment_block .suspend_button div {
        margin: 0 4px;
    }

    #register td {
        padding: 8px;
    }

    .top_bar .dropdown_col .dropdown {
        padding: 0;
    }

    .top_bar .dropdown_col {
        min-width: 240px;
    }

    header#header .date_time_block .login_col .login_button:hover {
        text-decoration: none;
    }
</style>

<div class='alert alert-dismissible alert-warning print_hide' id="errorMsg" style="display: none;">
    <b></b>
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
</div>


<?php //if (isset($direct) && !$direct) : 
?>
<div class="top_bar print_hide">
    <div class="inner_block">
        <?php
        //if ($customers_module_allowed):
        ?>
        <?php echo form_open($controller_name . "/change_mode", array('id' => 'mode_form', 'class' => 'form-horizontal')); ?>

        <div class="dropdown_col">
            <label><?php echo lang('sales_lang.sales_mode'); ?></label>
            <?php echo form_dropdown('mode', $modes, $mode, array(
                'onchange' => "$('#mode_form').submit();",
                'class' => 'dropdown selectpicker btn_register_mode show-menu-arrow',
                'style' => 'background-color:#e7e7e7; border:#e7e7e7; color:#484848;',
                'id' => 'change_mode', 'data-width' => 'fit'
            )); ?>

        </div>


        <?php
        if (count($stock_locations) > 1) {
        ?>
            <ul class="">
                <li class="pull-left">
                    <label class="control-label"><?php echo lang('sales_lang.sales_stock_location'); ?></label>
                </li>
                <li class="pull-left">
                    <?php echo form_dropdown('stock_location', $stock_locations, $stock_location, array('onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit')); ?>
                </li>
            </ul>
        <?php
        }
        ?>
        <?php echo form_close();
        //endif; 
        ?>

        <?php

        ?>
        <?php echo form_open($controller_name . "/change_type", array('id' => 'type_form', 'class' => 'form-horizontal')); ?>

        <div class="dropdown_col">
            <label>Sale Type</label>
            <?php echo form_dropdown('type', $types, $type, array(
                'onchange' => "$('#type_form').submit();",
                'class' => 'dropdown selectpicker btn_register_mode show-menu-arrow',
                'style' => 'background-color:#e7e7e7; border:#e7e7e7; color:#484848;',
                'id' => 'change_type', 'data-width' => 'fit'
            )); ?>

        </div>

        <?php echo form_close(); ?>


        <div class="suspended_block">
        <button id='show_pizza_sales_button' data-href='<?php echo base_url($controller_name . "/pizzaSuspendedWithItems"); ?>' title='Pizza completed order'>
                <span class="glyphicon glyphicon-th-list">&nbsp;</span>Pizza sale
            </button>

               <button id='show_cake_sales_button' data-href='<?php echo base_url($controller_name . "/cakeSuspendedWithItems"); ?>' title='Sale with cake item'>
                <span class="glyphicon glyphicon-th">&nbsp;</span>Cake sale
            </button>
            <?php
            if ($employees->has_grant('reports_sales', session()->get('person_id'))) {
            ?>

                <?php echo anchor(
                    $controller_name . "/manage",
                    '<span class="glyphicon glyphicon-list-alt">&nbsp;</span>' . lang('sales_lang.sales_takings'),
                    array('id' => 'sales_takings_button', 'title' => lang('sales_lang.sales_takings'))
                ); ?>

            <?php
            }
            ?>

            <button id='show_suspended_sales_button' data-href='<?php echo base_url($controller_name . "/suspendedWithItems"); ?>' title='<?php echo lang('sales_lang.sales_suspended_sales'); ?>'>
                <span class="glyphicon glyphicon-align-justify">&nbsp;</span><?php echo lang('sales_lang.sales_suspended_sales'); ?>
            </button>

            <button id='show_shortcuts_button' data-href='<?php echo base_url($controller_name . "/shortcuts"); ?>' title='View Shortcuts'>
                <span class="glyphicon glyphicon-align-justify">&nbsp;</span>Shortcuts</button>
        </div>


    </div>
</div>


<div class="inner_block print_hide">
    <?php
    $tabindex = 0;
    $priceIndex = 1000;
    $quantityIndex = 2000;
    $discountIndex = 3000;
    ?>

    <?php echo form_open($controller_name . "/add", array('id' => 'add_item_form', 'class' => 'form-horizontal')); ?>
    <div class="search_outer">
        <div class="search_block">
            <div class="search_heading">Find / Scan Item OR Receipt:</div>
            <input type="text" name="item" value="" id="item" class="search_input ui-autocomplete-input" tabindex="1" autocomplete="off">
        </div>
        <!--        <div class="new_item_button">-->
        <!--            <button class="input_button modal-dlg" value="New item"-->
        <!--                    id='new_item_button'-->
        <!--                    data-href='--><?php //echo base_url("items/view"); 
                                                ?>
        <!--'-->
        <!--                    data-btn-new='--><?php //echo lang('sales_lang.common_new') 
                                                    ?>
        <!--'-->
        <!--                    data-btn-submit='--><?php //echo lang('sales_lang.common_submit') 
                                                    ?>
        <!--'-->
        <!--                    title='--><?php //echo lang($sale_lang.controller_name . '_new_item'); 
                                            ?>
        <!--'>-->
        <!--                --><?php //echo lang($sale_lang.controller_name . '_new_item'); 
                                ?>
        <!--            </button>-->
        <!---->
        <!--        </div>-->
    </div>
    <!--search_outer-->
    <?php echo form_close(); ?>
</div>
<!--inner_block-->
<div class="clear"></div>


<div id="register_content">
<?php include(APPPATH . 'Views/sales/register_partial.php'); ?>

</div>

<?php //endif; 

?>


<!-- Suspended Sales Modal -->
<div class="modal fade" id="suspended_sales_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #337ab7;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color: white;">Suspended Sales</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cake Sales Modal -->
<div class="modal fade" id="cake_sales_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #337ab7;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color: white;">Cake Sales</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Pizza Sales Modal -->
<div class="modal fade" id="pizza_sales_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #337ab7;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color: white;">Pizza Sales</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shortcuts_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #337ab7;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color: white;">Tehzeeb POS Shortcuts</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        update_payment_type("<?php echo $payment_type; ?>");

        $("#item").autocomplete({
            source: '<?php echo base_url($controller_name . "/item_search"); ?>',
            minChars: 0,
            autoFocus: false,
            delay: 500,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $("#add_item_form").submit();
                return false;
            }
        });

        $('#item').focus();

        var body = $('html');

        // Shortcut keys
        body.on('keydown', 'body', function(e) {

            //get keycode
            //alert(e.which);return;

            var field = focused = $(':focus');
            var type = null;
            var id = null;
            if (field.data()) {
                type = field.data('type');
                id = field.data('id');
            }

            //on arrow keys up key and down key press
            if (e.keyCode == 40 || e.keyCode == 38) {

                if (type && (type == "cart" || type == "suspended_sale")) {

                    e.preventDefault();

                    var tab = field.attr("tabindex");

                    if (e.keyCode == 40) {
                        // down key
                        tab++;
                    } else if (e.keyCode == 38) {
                        // up key
                        tab--;
                    }

                    $("[tabindex='" + tab + "']").focus();

                }
            }

            // on ctrl q or f8 focus on quantity field
            else if ((e.metaKey || e.ctrlKey) && (e.which == 81) || e.which == 119) {
                e.preventDefault();
                $("[name='quantity'][type='text']").first().focus();
            }

            // on F7 focus on price field
            else if (e.which == 118) {
                e.preventDefault();
                $("[name='price'][type='text']").first().focus();
            }

            //on ctrl enter or *
            else if (((e.metaKey || e.ctrlKey) && (e.which == 13)) || e.which == 106) {
                //$('#add_payment_form').submit();
                //FAST SUBMIT WITHOUT ENTERING AMOUNT
                e.preventDefault();

                if ($('#finish_sale_button').length) {
                    $('#finish_sale_button').click();
                } else if ($('#add_payment_button').length) {
                    $('#add_payment_button').click();
                }

                //console.log($('#finish_sale_button').length);

                //console.log( "You pressed CTRL + enter" );
            } else if (e.which == 13) {
                // enter key press

                //Update Cart submit
                if (type) {
                    if (type == 'cart') {
                        e.preventDefault();
                        var cartItem = $('#cartItem' + id);

                        var cartPrice = cartItem.find('input[name=price]').val();
                        var cartQuantity = cartItem.find('input[name=quantity]').val();

                        if (cartPrice > 5000 || cartQuantity > 5000) {
                            if (!confirm("Are you sure quantity and price is correct?")) {
                                return false;
                            }
                        }


                        //                        console.log(cartItem.find('input[name=price]').val());
                        $.ajax({
                            url: $('#cart_' + id).attr('action'),
                            method: 'post',
                            data: {
                                location: cartItem.find('input[name=location]').val(),
                                price: cartPrice,
                                quantity: cartQuantity,
                                discount: cartItem.find('input[name=discount]').val(),
                                sale_type: $('#change_type').val(),
                                sale_mode: $('#change_mode').val()
                            },
                            success: function(data) {
                                //console.log(data);
                                $('#register_content').html(data);
                                $('#payment_types').selectpicker('refresh');

                                $('#item').val('');
                                $('#item').focus();

                                $('#cartItem' + id).effect('highlight', {
                                    color: '#18bc9c'
                                }, 3000);

                            },
                            error: function(data) {
                                $('#errorMsg b').text('cart not updated.');
                                $('#errorMsg').show();
                                //alert('something went wrong');
                                console.log(data);

                                $('#item').val('');
                                $('#item').focus();
                            }
                        });


                        //cartItem.submit();
                    }
                }

            } else if (e.which == 107) {
                //amount_tendered
                //focus payment field using +
                var payment_types = $('#payment_types').val();
                if (payment_types != "Credit Card") {
                    e.preventDefault();
                    $('#amount_tendered').val(0);
                    $('#amount_tendered').focus();
                }
            } else if (e.which == 113) {
                //focus item field using F2
                e.preventDefault();
                $('#item').val('');
                $('#item').focus();
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 88)) {
                //delete item from bill using ctrl x
                if (type) {
                    if (type == 'cart') {
                        e.preventDefault();
                        $('#btn_delete' + id)[0].click()
                    }
                }
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 66)) {
                //delete item from bill using ctrl b
                e.preventDefault();
                btn = $('.delete_payment').first();
                if (btn[0]) {
                    btn[0].click();
                }
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 82)) {
                //refresh page ctrl R
                e.preventDefault();
                btn = $('#reload_btn');
                if (btn[0]) {
                    btn[0].click();
                }
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 90)) {
                //remove / cancel bill using ctrl z
                e.preventDefault();
                if ($('#cancel_sale_button').length) {
                    $('#cancel_sale_button').click();
                }
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 71)) {
                e.preventDefault();
                //change payment mode using ctrl g
                //Change Payment Type


                var payment_types = $('#payment_types').val();

                var payment_dropdown_btn = $('#add_payment_form .dropdown_col button').first();

                var payment_dropdown_span = $('#add_payment_form .dropdown_col span').first();

                if (payment_types == "Cash") {
                    $('#payment_types').val('Credit Card');
                    payment_dropdown_btn.attr('title', 'Credit Card');
                    payment_dropdown_span.html('Credit Card');
                }
                // else if (payment_types == "Credit Card") {
                //     $('#payment_types').val('Check');
                //     payment_dropdown_btn.attr('title', 'Check');
                //     payment_dropdown_span.html('Check');
                // }
                else {
                    $('#payment_types').val('Cash');
                    payment_dropdown_btn.attr('title', 'Cash');
                    payment_dropdown_span.html('Cash');
                }
                // else if (payment_types == "Check") {
                //     $('#payment_types').val('Gift Card');
                //     payment_dropdown_btn.attr('title', 'Gift Card');
                //     payment_dropdown_span.html('Gift Card');
                // }
                // else if (payment_types == "Gift Card") {
                //     $('#payment_types').val('Cod');
                //     payment_dropdown_btn.attr('title', 'Cod');
                //     payment_dropdown_span.html('Cod');
                // }
                // else{
                // $('#payment_types').val('Cash');
                // payment_dropdown_btn.attr('title', 'Cash');
                // payment_dropdown_span.html('Cash');
                // }
                //$('#payment_types').selectpicker('refresh');

                change_payment_type();

            } else if ((e.metaKey || e.ctrlKey) && (e.which == 68)) {
                //discount shortcut
                //focus discount field using ctrl d
                e.preventDefault();
                $('#discount_percent').focus();
            } else if (e.which == 117) {
                e.preventDefault();
                //change register mode using F6

                mode = $('#change_mode').val();

                if (mode == 'sale') {
                    $('#change_mode').val('return');
                } else {
                    $('#change_mode').val('sale');
                }

                $('#mode_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 49)) {
                //change register type to normal using alt 1

                e.preventDefault();

                $('#change_type').val('normal');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 50)) {
                //change register type to breakfast using alt 2

                e.preventDefault();

                $('#change_type').val('breakfast');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 51)) {
                //change register type to complementary using alt 3

                e.preventDefault();

                $('#change_type').val('complementary');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 52)) {
                //change register type to burger using alt 4

                e.preventDefault();

                $('#change_type').val('burger');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 53)) {
                //change register type to cash counter using alt 5

                e.preventDefault();

                $('#change_type').val('cash-counter');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 54)) {
                //change register type to employee using alt 6

                e.preventDefault();

                $('#change_type').val('employee');

                $('#type_form').submit();

            } else if ((e.metaKey || e.altKey) && (e.which == 55)) {
                //change payment type using alt 7

                e.preventDefault();

                $('#payment_types').val('Cash');

                change_payment_type();

            } else if ((e.metaKey || e.altKey) && (e.which == 56)) {
                //change payment type using alt 8

                e.preventDefault();

                $('#payment_types').val('Credit Card');

                change_payment_type();

            } else if ((e.metaKey || e.altKey) && (e.which == 57)) {
                //change payment type using alt 9

                e.preventDefault();

                $('#payment_types').val('Check');

                change_payment_type();

            } else if ((e.metaKey || e.altKey) && (e.which == 48)) {
                //change payment type using alt 0

                e.preventDefault();

                $('#payment_types').val('Gift Card');

                change_payment_type();

            } else if ((e.metaKey || e.altKey) && (e.which == 80)) {
                //change payment type using alt 0

                e.preventDefault();

                $('#payment_types').val('Cod');

                change_payment_type();

            } else if ((e.metaKey || e.altKey) && (e.which == 81)) {
                //suspend sale with alt q
                e.preventDefault();
                $('#suspend_sale_button').click();
            } else if ((e.metaKey || e.altKey) && (e.which == 87)) {
                //un suspend sale with alt w
                e.preventDefault();
                $('#unsuspend-last-sale').click();
            } else if ((e.metaKey || e.ctrlKey) && (e.which == 192)) {
                //show shortcuts with ctrl `
                $('#show_shortcuts_button').click();
            } else if ((e.metaKey || e.altKey) && (e.which == 90)) {
                //suspend sale with alt z
                e.preventDefault();
                if (!$('#suspended_sales_modal').is(':visible')) {
                    $('#show_suspended_sales_button').click();
                }

                $('.suspended_sale_id').first().focus();
            } else if ((e.metaKey || e.altKey) && (e.which == 67)) {
                //cake sale with alt c
                if (!$('#cake_sales_modal').is(':visible')) {
                    $('#show_cake_sales_button').click();
                }
                $('.suspended_sale_id').first().focus();
            } else if ((e.metaKey || e.altKey) && (e.which == 77)) {
                //pizza sale with alt m
                if (!$('#pizza_sales_modal').is(':visible')) {
                    console.log('open');
                    $('#show_pizza_sales_button').click();
                }
                $('.suspended_sale_id').first().focus();
            }

        });


        $('#item').keypress(function(e) {
            if (e.which == 13) {
                $('#add_item_form').submit();
                return false;
            }
        });

        //altered

        // $('#item').keypress(function (e) {

        //     if (e.which == 13) {
        //         if($(this).val().length>5){
        //             var oId = $(this).val();
        //             alert(oId);
        //             $.get( "<?php //echo base_url('sales/getId/'); 
                                ?>"+oId)
        //               .done(function( data ) {
        //                 obj = JSON.parse(data);
        //                 $('#item').val('PN '+obj.order_id)
        //                 $('#add_item_form').submit();
        //             });
        //             $('#item').val('');
        //             return false;
        //         }else{
        //             $('#add_item_form').submit();
        //             return false;
        //         }
        //     }
        // });

        $('#item').blur(function() {
            //$(this).val("<?php //echo lang('sales_lang.sales_start_typing_item_name'); 
                            ?>");
        });

        var clear_fields = function() {
            if ($(this).val().match("<?php echo lang('sales_lang.sales_start_typing_item_name') . '|' . lang('sales_lang.sales_start_typing_customer_name'); ?>")) {
                $(this).val('');
            }
        };

        $("#customer").autocomplete({
            source: '<?php echo base_url("customers/suggest"); ?>',
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $("#select_customer_form").submit();
            }
        });

        $('#item, #customer').click(clear_fields).dblclick(function(event) {
            $(this).autocomplete("search");
        });

        $('#customer').blur(function() {
            $(this).val("<?php echo lang('sales_lang.sales_start_typing_customer_name'); ?>");
        });

        $('#comment').keyup(function() {
            $.post('<?php echo base_url($controller_name . "/set_comment"); ?>', {
                comment: $('#comment').val()
            });
        });

        <?php
        if ($appData['invoice_enable'] == TRUE) {
        ?>
            $('#sales_invoice_number').keyup(function() {
                $.post('<?php echo base_url($controller_name . "/set_invoice_number"); ?>', {
                    sales_invoice_number: $('#sales_invoice_number').val()
                });
            });

            var enable_invoice_number = function() {
                var enabled = $("#sales_invoice_enable").is(":checked");
                $("#sales_invoice_number").prop("disabled", !enabled).parents('tr').show();
                return enabled;
            };

            enable_invoice_number();

            $("#sales_invoice_enable").change(function() {
                var enabled = enable_invoice_number();
                $.post('<?php echo base_url($controller_name . "/set_invoice_number_enabled"); ?>', {
                    sales_invoice_number_enabled: enabled
                });
            });
        <?php
        }
        ?>

        $("#sales_print_after_sale").change(function() {
            $.post('<?php echo base_url($controller_name . "/set_print_after_sale"); ?>', {
                sales_print_after_sale: $(this).is(":checked")
            });
        });

        $('#email_receipt').change(function() {
            $.post('<?php echo base_url($controller_name . "/set_email_receipt"); ?>', {
                email_receipt: $('#email_receipt').is(':checked') ? '1' : '0'
            });
        });

        body.on('click', '#finish_sale_button', function() {
            $('#buttons_form').attr('action', '<?php echo base_url($controller_name . "/complete"); ?>');
            $('#buttons_form').submit();
        });

        body.on('click', '#suspend_sale_button', function() {
            $('#buttons_form').attr('action', '<?php echo base_url($controller_name . "/suspend"); ?>');
            $('#buttons_form').submit();
        });

        body.on('click', '#cancel_sale_button', function() {
            if (confirm('<?php echo lang("sales_lang.sales_confirm_cancel_sale"); ?>')) {
                $('#buttons_form').attr('action', '<?php echo base_url($controller_name . "/cancel"); ?>');
                $('#buttons_form').submit();
            }
        });

        body.on('click', '#add_payment_button', function() {

            $('#error_message_box').html('').fadeOut();

            var total_price = $.trim($('#price_block').text());
            total_price = parseInt(total_price.replace(',', ''));

            if ($("#payment_types").val() === "<?php echo lang('sales_lang.sales_giftcard'); ?>") {
                if (total_price > 0) {
                    if ($('#amount_tendered').val() == '') {
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_amount_required'); ?></label></li>').fadeIn();
                    } else if (!$.isNumeric($('#amount_tendered').val())) {
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_amount_required_numeric'); ?></label></li>').fadeIn();
                    } else if ($('#amount_giftcard_tendered').val().length < 3) {
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_not_valid'); ?></label></li>').fadeIn();
                    } else if ($('#amount_giftcard_tendered').val() == '') {
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_required'); ?></label></li>').fadeIn();
                        val
                    } else if (($('#amount_giftcard_tendered').data("value") <= 0 || $('#amount_giftcard_tendered').data("value") == '') && $('#amount_giftcard_tendered').data("voucher") == '') {
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_expire'); ?></label></li>').fadeIn();
                    } else if ($('#amount_tendered').val() < $('#amount_giftcard_tendered').data("value") && $('#amount_giftcard_tendered').data("voucher") == '') {
                        var giftMinVal = $('#amount_giftcard_tendered').data("value");
                        $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_low_limit'); ?>' + giftMinVal + '</label></li>').fadeIn();
                    } else {
                        $('#add_payment_form').submit();
                    }
                } else {
                    $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_low_limit'); ?></label></li>').fadeIn();
                }
            } else {
                $('#add_payment_form').submit();

            }

        });

        body.on('submit', '#add_payment_form', function(e) {
            if (!validateAmount()) {
                e.preventDefault();
                return false;
            }
            
            $('#amount_tendered').hide();
            $('#add_payment_button').attr('disabled', 'disabled');

        });

        $("#payment_types").change(check_payment_type_giftcard).ready(check_payment_type_giftcard);

        body.on('keypress', '#cart_contents input', function(event) {
            if (event.which == 13) {
                $(this).parents("tr").prevAll("form:first").submit();
            }
        });

        //        $("#amount_tendered").keypress(function (event) {
        //            if (event.which == 13) {
        //                $('#add_payment_form').submit();
        //            }
        //        });

        body.on('keypress', '#finish_sale_button', function(event) {
            if (event.which == 13) {
                $('#finish_sale_form').submit();
            }
        });

        body.on('keyup', '#amount_giftcard_tendered', function(event) {

            var giftcard_num = $('#amount_giftcard_tendered').val();

            var total_price = $.trim($('#price_block').text());

            total_price = parseInt(total_price.replace(',', ''));

            var amount_tendered = 0;
            $('#error_message_box').html('').fadeOut();

            // if total price is > defined giftcard checkout limit
            if (total_price > 0) {
                // if giftcard code contains 3 or more characters
                if (giftcard_num.length > 2) {
                    $.get("<?php echo base_url('sales/giftcard_value/'); ?>" + giftcard_num)
                        .done(function(data) {
                            obj = JSON.parse(data);
                            if (obj.value == 0) {
                                $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_expire'); ?></label></li>').fadeIn();
                                $("#amount_giftcard_tendered").data("voucher", '');
                            } else if (total_price >= obj.value) {
                                $('#error_message_box').html('<li><label id="item_number-error" class="has-success" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_success'); ?></label></li>').fadeIn();
                                amount_tendered = obj.value;

                                $('#amount_tendered').val(total_price - amount_tendered);
                                $("#amount_giftcard_tendered").data("value", amount_tendered);
                                $("#amount_giftcard_tendered").data("voucher", 'Applied');

                            } else {
                                $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_low_limit'); ?>' + obj.value + '</label></li>').fadeIn();
                                $("#amount_giftcard_tendered").data("value", obj.value);
                                $("#amount_giftcard_tendered").data("voucher", '');
                            }
                        });
                } else {
                    $('#amount_tendered').val(total_price - amount_tendered);
                    $("#amount_giftcard_tendered").data("value", amount_tendered);
                }
            } else {
                $('#error_message_box').html('<li><label id="item_number-error" class="has-error" style="display: inline-block;" for="item_number"><?php echo lang('sales_lang.sales_giftcard_low_limit'); ?></label></li>').fadeIn();
            }
        });


        body.on('keyup', '#amount_tendered', function(event) {

            // amount_tendered entered value is a numeric value else giftcard number
            var total_price = $.trim($('#price_block').text());

            total_price = parseInt(total_price.replace(',', ''));

            var amount_tendered = parseInt($('#amount_tendered').val());

            var sales_giftcard_number = 0;
            if ($('#amount_giftcard_tendered').data('value'))
                sales_giftcard_number = parseInt($('#amount_giftcard_tendered').data('value'));

            if (isNaN(amount_tendered)) {
                amount_tendered = 0;
            }

            $('#remaining_balance').val(total_price - amount_tendered - sales_giftcard_number);
        });




        //$('#add_payment_form').submit();

        dialog_support.init("a.modal-dlg, button.modal-dlg");

        table_support.handle_submit = function(resource, response, stay_open) {
            if (response.success) {
                if (resource.match(/customers$/)) {
                    $("#customer").val(response.id);
                    $("#select_customer_form").submit();
                } else {
                    var $stock_location = $("select[name='stock_location']").val();
                    $("#item_location").val($stock_location);
                    $("#item").val(response.id);
                    if (stay_open) {
                        $("#add_item_form").ajaxSubmit();
                    } else {
                        $("#add_item_form").submit();
                    }
                }
            }
        };

        //

        /**
         * item submit search bar submit
         */
        var item;
        $(document).on('submit', '#add_item_form', function(e) {
            e.preventDefault();
            item = $('#item').val();
            // alert(item);
            // return false;
            $('#item').val('');
            $('#item').focus();

            $.ajax({
                url: "<?php echo base_url('sales/add'); ?>",
                method: 'post',
                data: {
                    item: item
                },
                success: function(data) {
                    //console.log(data);
                    $('#register_content').html(data);
                    $('#payment_types').selectpicker('refresh');

                    var firstRow = $('tbody tr:first');

                    firstRow.effect('highlight', {}, 3000);

                    //focus on quantity after item add
                    if ($('#editQuantity').val() == 1) {
                        firstRow.find('.quantity').focus();
                    }

                },
                error: function(data) {
                    $('#errorMsg b').text('unable to add item');
                    $('#errorMsg').show();
                    //alert('something went wrong');
                    console.log(data);
                }
            });

            return false;
        });

        /**
         * Suspended Sales Button Click
         */
        $(document).on('click', '#show_suspended_sales_button', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).data('href'),
                method: 'get',
                success: function(data) {
                    //console.log(data);
                    $('#suspended_sales_modal .modal-body').html(data);
                },
                error: function(data) {
                    $('#errorMsg b').text('something went wrong while opening suspended sales');
                    $('#errorMsg').show();
                    //alert('something went wrong');
                    console.log(data);
                }
            });


            $('#suspended_sales_modal').modal('show');

        });


        $('#suspended_sales_modal').on('shown.bs.modal', function() {
            $('.suspended_sale_id').first().focus();
        });


         /**
         * Cake Sales Button Click
         */
        $(document).on('click', '#show_cake_sales_button', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).data('href'),
                method: 'get',
                success: function(data) {
                    //console.log(data);
                    $('#cake_sales_modal .modal-body').html(data);
                },
                error: function(data) {
                    $('#errorMsg b').text('something went wrong while opening suspended sales');
                    $('#errorMsg').show();
                    //alert('something went wrong');
                    console.log(data);
                }
            });


            $('#cake_sales_modal').modal('show');

        });

        $('#cake_sales_modal').on('shown.bs.modal', function() {
            $('.suspended_sale_id').first().focus();
        });

         /**
         * Pizza Sales Button Click
         */
        $(document).on('click', '#show_pizza_sales_button', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).data('href'),
                method: 'get',
                success: function(data) {
                    //console.log(data);
                    $('#pizza_sales_modal .modal-body').html(data);
                },
                error: function(data) {
                    $('#errorMsg b').text('something went wrong while opening suspended sales');
                    $('#errorMsg').show();
                    //alert('something went wrong');
                    console.log(data);
                }
            });


            $('#pizza_sales_modal').modal('show');

        });

        $('#pizza_sales_modal').on('shown.bs.modal', function() {
            $('.suspended_sale_id').first().focus();
        });



        /**
         * Suspended Sales Button Click
         */
        $(document).on('click', '#show_shortcuts_button', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).data('href'),
                method: 'get',
                success: function(data) {
                    //console.log(data);
                    $('#shortcuts_modal .modal-body').html(data);
                },
                error: function(data) {
                    $('#errorMsg b').text('something went wrong while opening shortcuts');
                    $('#errorMsg').show();
                    //alert('something went wrong');
                    console.log(data);
                }
            });


            $('#shortcuts_modal').modal('show');

        });


        $(document).on('change', '#payment_types', function(e) {
            change_payment_type();
        });



    }); //document ready end

    function updateCartItem(id) {
        var cartItem = $('#cartItem' + id);

        var cartPrice = cartItem.find('input[name=price]').val();
        var cartQuantity = cartItem.find('input[name=quantity]').val();

        if (cartPrice > 5000 || cartQuantity > 5000) {
            if (!confirm("Are you sure quantity and price is correct?")) {
                return false;
            }
        }

        $.ajax({
            url: $('#cart_' + id).attr('action'),
            method: 'post',
            data: {
                location: cartItem.find('input[name=location]').val(),
                price: cartPrice,
                quantity: cartQuantity,
                discount: cartItem.find('input[name=discount]').val(),
                sale_type: $('#change_type').val(),
                sale_mode: $('#change_mode').val()
            },
            success: function(data) {
                //console.log(data);
                $('#register_content').html(data);
                $('#payment_types').selectpicker('refresh');

                $('#item').val('');
                $('#item').focus();

                $('#cartItem' + id).effect('highlight', {
                    color: '#18bc9c'
                }, 3000);

            },
            error: function(data) {
                $('#errorMsg b').text('cart not updated.');
                $('#errorMsg').show();
                //alert('something went wrong');
                console.log(data);

                $('#item').val('');
                $('#item').focus();
            }
        });
    }

    // function validateAmount() {

    //     var total_amount = parseInt($('.price_block').text().replace(',', ''));
    //     var payment_amount = parseInt($('#amount_tendered').val().replace(',', ''));

    //     if (payment_amount > 5000) {
    //         if (!confirm("Are you sure amount is correct?")) {
    //             return false;
    //         }
    //     }

    //     if ($("#payment_types").val() != "<?php echo lang('sales_lang.sales_giftcard'); ?>") {
    //         if (payment_amount >= total_amount) {
    //             return true;
    //         } else {
    //             alert('Please enter correct amount');
    //             return false;
    //         }
    //     }
    //     return true;

    // }   
    function validateAmount() {
        
        var cake_invoice = "<?php echo isset($cake_invoice) &&  $cake_invoice!= null; ?>";
        var total_amount = parseInt($('.price_block').text().replace(',', ''));
        var payment_amount = parseInt($('#amount_tendered').val().replace(',', ''));
        var paid_amount = parseInt($('#paid_amount').text().replace(',', ''));
        var paid_check = paid_amount + payment_amount;
        if (payment_amount > 5000) {
            if (!confirm("Are you sure amount is correct?")) {
                return false;
            }
        }

        if ($("#payment_types").val() != "<?php echo lang('sales_lang.sales_giftcard'); ?>") {
            if (payment_amount >= total_amount || paid_check >= total_amount || cake_invoice) {
                return true;
            } else {
                alert('Please enter correct amount');
                return false;
            }
        }
        return true;

    }

    function check_payment_type_giftcard() {
        if ($("#payment_types").val() == "<?php echo lang('sales_lang.sales_giftcard'); ?>") {
            $("#amount_tendered_label").html("<?php echo lang('sales_lang.sales_amount_tendered'); ?>");
            // $("#amount_tendered:enabled").val('').focus();
            $("#amount_tendered:enabled").val('<?php echo to_currency_no_money($amount_due); ?>');
        } else {
            $("#amount_tendered_label").html("<?php echo lang('sales_lang.sales_amount_tendered'); ?>");
            $("#amount_tendered:enabled").val('<?php echo to_currency_no_money($amount_due); ?>');
        }
    }


    function change_payment_type() {
        window.location.href = 'sales/change_payment_type?payment_type=' + $('#payment_types').val();

    }


    function update_payment_type(type) {
        var payment_types = $('#payment_types').val();

        var payment_dropdown_btn = $('#add_payment_form .dropdown_col button').first();

        var payment_dropdown_span = $('#add_payment_form .dropdown_col span').first();

        $('#payment_types').val(type);
        payment_dropdown_btn.attr('title', type);
        payment_dropdown_span.html(type);

    }

    function printdoc() {
        // receipt layout sanity check
        if ($("#receipt_items, #items, #table_holder").length > 0) {
            // install firefox addon in order to use this plugin
            if (window.jsPrintSetup) {
                // set top margins in millimeters
                jsPrintSetup.setOption('marginTop', '<?php echo $appData['print_top_margin']; ?>');
                jsPrintSetup.setOption('marginLeft', '<?php echo $appData['print_left_margin']; ?>');
                jsPrintSetup.setOption('marginBottom', '<?php echo $appData['print_bottom_margin']; ?>');
                jsPrintSetup.setOption('marginRight', '<?php echo $appData['print_right_margin']; ?>');

                <?php if (!$appData['print_header'])
                {
                ?>
                // set page header
                jsPrintSetup.setOption('headerStrLeft', '');
                jsPrintSetup.setOption('headerStrCenter', '');
                jsPrintSetup.setOption('headerStrRight', '');
                <?php
                }
                if (!$appData['print_footer'])
                {
                ?>
                // set empty page footer
                jsPrintSetup.setOption('footerStrLeft', '');
                jsPrintSetup.setOption('footerStrCenter', '');
                jsPrintSetup.setOption('footerStrRight', '');
                <?php
                }
                ?>

                var printers = jsPrintSetup.getPrintersList().split(',');
                // get right printer here..
                for (var index in printers) {
                    var default_ticket_printer = window.localStorage && localStorage["receipt_printer"];
                    var selected_printer = printers[index];
                    if (selected_printer == default_ticket_printer) {
                        // select epson label printer
                        jsPrintSetup.setPrinter(selected_printer);
                        // clears user preferences always silent print value
                        // to enable using 'printSilent' option
                        jsPrintSetup.clearSilentPrint();
                        <?php if (!$appData['print_silently'])
                        {
                        ?>
                        // Suppress print dialog (for this context only)
                        jsPrintSetup.setOption('printSilent', 1);
                        <?php
                        }
                        ?>
                        // Do Print
                        // When print is submitted it is executed asynchronous and
                        // script flow continues after print independently of completetion of print process!
                        jsPrintSetup.print();
                    }
                }
            }
            else {
                window.print();
            }
        }
    }
</script>

<?php  include(APPPATH . 'Views/partial/footer.php'); 

if (isset($direct) && $direct) {
    return redirect()->to('sales/complete');
}
?>


<?php echo view('sales/receipt_short');?>
<script>
    $(document).ready(function() {
        <?php if (service('router')->methodName() == "complete") : ?>
            printdoc();
           <?php //$cli->upload();?>
            window.location.href = "<?php echo base_url('sales'); ?>";
        <?php endif; ?>
    });
</script>