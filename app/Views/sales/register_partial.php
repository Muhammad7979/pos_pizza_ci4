<?php $tabindex = 0;
$priceIndex = 1000;
$quantityIndex = 2000;
$discountIndex = 3000; ?>
<div class="table_block print_hide">
    <div class="top_block">
        <div class="inner_block">
            <div class="table_outer">


                <input type="hidden" value="<?php echo isset($edit_quantity) ? $edit_quantity : 0; ?>" id="editQuantity" />

                <!-- Sale Items List -->
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Delete</th>
                        <th>Item #</th>
                        <th class="item">Item Name</th>
                        <th>Price</th>
                        <th>QTY</th>
                        <th style="display: none;">Discount</th>
                        <th>Total</th>
                        <th class="last">Update</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    
                    if (count($cart) == 0) {
                        ?>
                        <tr>
                            <td style="width: 100%; text-align: center;">
                                <div style="background-color:#fff; border:#fff;
                                color:#2c3e50; font-weight:400; border-radius: 0;"
                                     class='alert'><?php echo lang('sales_lang.sales_no_items_in_cart'); ?></div>
                            </td>
                        </tr>
                        <?php
                    } else {
                        $i = 0;
                        foreach (array_reverse($cart, true) as $line => $item) {
                        
                            ?>
                            <?php echo form_open($controller_name . "/edit_item/$line", array('class' => 'form-horizontal',
                                'id' => 'cart_' . $line)); ?>
                            <tr id="cartItem<?php echo $line; ?>" <?php echo ($i++ % 2 == 0) ? "class='white_color'" : '' ?>>
                                <td><?php echo $i; ?></td>
                                <td><?php echo anchor($controller_name . "/delete_item/$line",
                                        '<span class="glyphicon glyphicon-trash"></span>', "id='btn_delete$line'"
                                    ); ?></td>
                                <td><?php echo $item['item_number']; ?></td>
                                <td style="align: center;" class="item">
                                    <?php echo $item['name']; ?>
                                    <?php echo form_hidden('location', $item['item_location']); ?>
                                </td>

                                <td><?php ++$priceIndex;
                                    echo form_input(array('name' => 'price',
                                        'class' => "input price", 'value' => to_currency_no_money($item['price']),
                                        'data-type' => 'cart', 'data-id' => $line,
                                        'tabindex' => $priceIndex, 'onfocus' => "this.select();")); ?></td>

                                <td>
                                    <?php
                                        ++$quantityIndex;
                                        echo form_input(array('name' => 'quantity',
                                            'data-type' => 'cart', 'data-id' => $line,
                                            'class' => 'input quantity', 'value' => to_quantity_decimals($item['quantity']),
                                            'tabindex' => $quantityIndex, 'onfocus' => "this.select();"));
                                    ?>
                                </td>

                                <?php
                                $item_discount = to_decimals($item['discount'], 0);

                                if (isset($total_discount)) {
                                    if ($item_discount > $total_discount) {
                                        $total_discount = $item_discount;
                                    }
                                } else {
                                    $total_discount = $item_discount;
                                }

                                ?>

                                <td style="display:none;"><?php ++$discountIndex;
                                    echo form_input(
                                        array('name' => 'discount', 'class' => 'input',
                                            'value' => to_decimals($item['discount'], 0),
                                            'data-type' => 'cart', 'data-id' => $line,

                                            'tabindex' => $discountIndex, 'onfocus' => "this.select();")); ?></td>

                                <td><?php echo to_currency($item['price'] * $item['quantity'] - $item['price'] * $item['quantity'] * $item['discount'] / 100); ?></td>
                                <td>
                                    <!-- <a href="javascript:" onclick="updateCartItem(<?php echo $line ?>)"
                                       title=<?php //echo lang('sales_lang.sales_update') ?>><span
                                                class="glyphicon glyphicon-refresh"></span></a> -->

                                    <a href="javascript:document.getElementById('<?php echo 'cart_' . $line ?>').submit();"
                                       title=<?php echo lang('sales_lang.sales_update') ?>><span
                                                class="glyphicon glyphicon-refresh"></span></a>
                                    </td>
                            </tr>
                            <!--                            <tr>-->
                            <!--                                --><?php
//                                if ($item['allow_alt_description'] == 1) {
//                                    ?>
                            <!--                                    <td style="color: #2F4F4F;">--><?php //echo lang('sales_lang.sales_description_abbrv'); ?><!--</td>-->
                            <!--                                    --><?php
//                                }
//                                ?>
                            <!---->
                            <!--                                <td colspan='2' style="text-align: left;">-->
                            <!--                                    --><?php
//                                    if ($item['allow_alt_description'] == 1) {
//                                        echo form_input(array('name' => 'description', 'class' => 'form-control input-sm', 'value' => $item['description']));
//                                    } else {
//                                        if ($item['description'] != '') {
//                                            echo $item['description'];
//                                            echo form_hidden('description', $item['description']);
//                                        } else {
//                                            echo lang('sales_lang.sales_no_description');
//                                            echo form_hidden('description', '');
//                                        }
//                                    }
//                                    ?>
                            <!--                                </td>-->
                            <!--                                <td>&nbsp;</td>-->
                            <!--                                <td style="color: #2F4F4F;">-->
                            <!--                                    --><?php
//                                    if ($item['is_serialized'] == 1) {
//                                        echo lang('sales_lang.sales_serial');
//                                    }
//                                    ?>
                            <!--                                </td>-->
                            <!--                                <td colspan='4' style="text-align: left;">-->
                            <!--                                    --><?php
//                                    if ($item['is_serialized'] == 1) {
//                                        echo form_input(array('name' => 'serialnumber', 'class' => 'form-control input-sm', 'value' => $item['serialnumber']));
//                                    } else {
//                                        echo form_hidden('serialnumber', '');
//                                    }
//                                    ?>
                            <!--                                </td>-->
                            <!--                            </tr>-->
                            <?php echo form_close(); ?>
                            <?php
                        }
                    }
                    ?>

                    </tbody>


                    <?php if (count($cart) > 0): ?>
                        <tfoot>
                        <tr>
                            <td class="total">Sub Total</td>
                            <td class="price_text">
                                <div
                                        class="price"><?php echo to_currency($appData['tax_included'] ? $tax_exclusive_subtotal : $subtotal); ?></div>
                            </td>
                        </tr>
                        </tfoot>

                    <?php endif; ?>

                </table>
            </div>

            <?php
            // Only show this part if there are Items already in the sale.
            //if (count($cart) > 0):
            ?>

            <div class="payment_block">
                <div class="price_block" id="price_block"><?php echo to_currency_no_money($total); ?></div>
                <div class="bottom_block">
                    <h6>Payment Total: <span><?php echo to_currency($payments_total); ?></span></h6>
                    <h6>Amount Due: <span><?php echo to_currency($amount_due); ?></span></h6>
                    <div>

                        <?php
                        // Show Complete sale button instead of Add Payment if there is no amount due left
                        if ($payments_cover_total) {
                            ?>
                            <?php echo form_open($controller_name . "/add_payment", array('id' => 'add_payment_form',
                                'class' => 'form-horizontal')); ?>

                            <div class="dropdown_col">
                                <p><?php echo lang('sales_lang.sales_payment'); ?></p>

                                <div style="float: right;">
                                    <?php echo form_dropdown('payment_type', $payment_options, array(),
                                        array('id' => 'payment_types', 'class' => 'selectpicker show-menu-arrow',
                                            'data-style' => 'input', 'data-width' => '145px',
                                            )); ?>
                                </div>

                            </div>
                            <div class="clear"></div>
                            <div class="dropdown_col">
                                <p id="amount_tendered_label"><?php
                                    echo lang('sales_lang.sales_amount_tendered'); ?></p>
                                <?php echo form_input(array('name' => 'amount_tendered',
                                    'id' => 'amount_tendered', 'class' => 'input',
                                    'autocomplete' => 'off',
                                    'disabled' => 'disabled', 'value' => '0',
                                    'tabindex' => ++$tabindex, 'onfocus' => "this.select();")); ?>
                            </div>

                            <?php echo form_close(); ?>

                        <div class="dropdown_col">
                            <p>Balance: </p>
                            <input type="text" name="remaining_balance" title="Balance"
                                   value="<?php echo $last_amount_change; ?>" id="remaining_balance" class="input"
                                   disabled="disabled" onfocus="this.select();">
                        </div>
                            <div class="clear"></div>

                            <button class="add_payment_button"
                                <?php if (count($cart) < 1): echo 'disabled style="cursor: not-allowed"'; endif; ?>
                                    tabindex='<?php echo ++$tabindex; ?>'
                                    id='finish_sale_button'><span
                                        class="glyphicon glyphicon glyphicon-ok"
                                ></span>&nbsp;&nbsp;Complete
                            </button>

                            <?php
                        } else {
                            ?>
                            <?php echo form_open($controller_name . "/add_payment", array('id' => 'add_payment_form',
                                'class' => 'form-horizontal')); ?>

                            <div class="dropdown_col">
                                <p><?php echo lang('sales_lang.sales_payment'); ?></p>
                                <div style="float: right;">
                                <?php echo form_dropdown('payment_type', $payment_options, $sale_lib->get_payment_type(),
                                    array('id' => 'payment_types', 'class' => 'selectpicker show-menu-arrow',
                                        'data-style' => 'input', 'data-width' => '145px')); ?>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="dropdown_col">
                                <p id="amount_tendered_label"><?php
                                    echo lang('sales_lang.sales_amount_tendered'); ?></p>
                                <?php echo form_input(array('name' => 'amount_tendered',
                                    'id' => 'amount_tendered', 'autocomplete' => 'off',
                                    'class' => 'input', 'value' => to_currency_no_money($amount_due),
                                    'tabindex' => ++$tabindex, 'onfocus' => "this.select();")); ?>
                            </div>

                            <?php if(isset($payment_type) && $payment_type==lang('sales_lang.sales_giftcard')){ ?>
                                <div class="dropdown_col">
                                    <p id="amount_giftcard_label"><?php
                                    echo lang('sales_lang.sales_giftcard_number'); ?></p>
                                    <?php echo form_input(array('name' => 'amount_giftcard_tendered',
                                        'id' => 'amount_giftcard_tendered', 'autocomplete' => 'off',
                                        'class' => 'input', 'value' => '', 'data-value' => '',
                                        'tabindex' => ++$tabindex, 'onfocus' => "this.select();")); ?>
                                </div>
                                <ul id="error_message_box" class="error_message_box error_message_box_sale"></ul>
                                
                            <?php } ?>
                            <?php if(isset($payment_type) && $payment_type==lang('sales_lang.sales_giftcard') ){ ?>
                            <div class="dropdown_col " id="sales_payment_type" style="display: none;">
                                    <label class="radio-inline">
                                    <?php echo form_radio(array(
                                        'name' => 'sales_payment_type',
                                        'value' => 'sales_cash',
                                        'checked'=>TRUE,
                                    'tabindex' => ++$tabindex,)); ?>
                                    <?php echo lang('sales_lang.sales_cash'); ?>
                                </label>
                                <label class="radio-inline">
                                    <?php echo form_radio(array(
                                        'name' => 'sales_payment_type',
                                        'value' => 'sales_credit',
                                        'checked'=>FALSE,
                                    'tabindex' => ++$tabindex,)); ?>
                                    <?php echo lang('sales_lang.sales_credit'); ?>
                                </label>
                            </div>
                            <?php } ?>
                            <?php echo form_close(); ?>

                            <div class="dropdown_col">
                                <p>Balance: </p>
                                <input type="text" name="remaining_balance" title="Balance"
                                       value="0" id="remaining_balance" class="input"
                                       disabled="disabled" onfocus="this.select();">
                            </div>

                            <div class="clear"></div>

                            <button class="add_payment_button" tabindex='<?php echo ++$tabindex; ?>'
                                    id='add_payment_button'><span
                                        class="glyphicon glyphicon-credit-card"
                                ></span>&nbsp;&nbsp;Add Payment
                            </button>

                            <?php
                        }
                        ?>

                        <?php
                        // Only show this part if there is at least one payment entered.
                        if (count($payments) > 0) {
                            ?>
                            <table class="sales_table_100" id="register">
                                <thead>
                                <tr>
                                    <th style="width: 10%;"><?php echo lang('common_lang.common_delete'); ?></th>
                                    <th style="width: 60%;"><?php echo lang('sales_lang.sales_payment_type'); ?></th>
                                    <th style="width: 20%;"><?php echo lang('sales_lang.sales_payment_amount'); ?></th>
                                </tr>
                                </thead>

                                <tbody id="payment_contents">
                                <?php
                                foreach ($payments as $payment_id => $payment) {
                                    ?>
                                    <tr>
                                        <td><?php echo anchor($controller_name . "/delete_payment/$payment_id",
                                                '<span class="glyphicon glyphicon-trash"></span>',
                                                "data-type='delete_payment' class='delete_payment'"); ?></td>
                                        <td><?php echo $payment['payment_type']; ?></td>
                                        <td style="text-align: right;"><?php echo to_currency($payment['payment_amount']); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php
                        }
                        ?>

                    </div>

                    <div class="clear"></div>

                    <?php echo form_open($controller_name . "/cancel", array('id' => 'buttons_form')); ?>

                    <div class="suspend_button" id="buttons_sale">
                        <div <?php if (count($cart) < 1): echo 'disabled style="cursor: not-allowed"';
                        else: echo "id='suspend_sale_button'"; endif; ?>>Suspend
                        </div>
                        <div class="cancel" id='cancel_sale_button'>Cancel</div>

                    </div>

                    <?php echo form_close(); ?>

                    <div class="clear"></div>

                    <?php if (count($cart) > 0): ?>
                        <div class="dropdown_col">
                            <p><span>Discount %: </span>
                            </p>
                            <form action="<?php echo base_url() . $controller_name . "/discount/" ?>" method="get">
                                <?php echo form_input(array('name' => 'discount',
                                    'class' => 'input', 'id' => 'discount_percent',
                                    'value' => isset($total_discount) ? $total_discount : 0,
                                    'onfocus' => "this.select();",
                                    'tabindex' => ++$tabindex)); ?>
                                <button type="submit" style="display:none;">Discount</button>
                            </form>

                        </div>
                    <?php endif; ?>

                    <div class="clear"></div>
                    <div class="last_bill">
                        <h6>Last Bill: <span><?php echo $last_total; ?></span></h6>
                        <h6>Cash: <span><?php echo $last_payments_total; ?></span></h6>
                        <h6 class="last">Change Due: <span><?php echo $last_amount_change; ?></span></h6>

                    </div>

                    <?php
                    //un suspend last sale
                    if ($last_suspended_sale):
                        echo form_open('sales/unsuspend', [
                            'id' => 'unsuspend-last-sale-form',
                            'style' => 'display: none;'
                        ]); ?>
                        <input type="text"
                               name="suspended_sale_id"
                               value="<?php echo $last_suspended_sale; ?>"/>
                        <input type="submit" name="submit" id="unsuspend-last-sale"
                               value="<?php echo lang('sales_lang.sales_unsuspend'); ?>"
                               class="btn btn-primary btn-xs pull-right">
                    <?php endif;
                    echo form_close(); ?>

                </div>


            </div>

            <?php
            //endif;
            ?>

        </div>
    </div>
</div>


<!-- Overall Sale -->
<div id="overall_sale" class="panel panel-default print_hide" style="display: none;">
    <div class="panel-body">
        <!--        --><?php
        //        if (isset($customer)) {
        //            ?>
        <!--            <table class="sales_table_100">-->
        <!--                <tr>-->
        <!--                    <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer"); ?><!--</th>-->
        <!--                    <th style="width: 45%; text-align: right;">--><?php //echo $customer; ?><!--</th>-->
        <!--                </tr>-->
        <!--                --><?php
        //                if (!empty($customer_email)) {
        //                    ?>
        <!--                    <tr>-->
        <!--                        <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer_email"); ?><!--</th>-->
        <!--                        <th style="width: 45%; text-align: right;">-->
        <?php //echo $customer_email; ?><!--</th>-->
        <!--                    </tr>-->
        <!--                    --><?php
        //                }
        //                ?>
        <!--                --><?php
        //                if (!empty($customer_address)) {
        //                    ?>
        <!--                    <tr>-->
        <!--                        <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer_address"); ?><!--</th>-->
        <!--                        <th style="width: 45%; text-align: right;">-->
        <?php //echo $customer_address; ?><!--</th>-->
        <!--                    </tr>-->
        <!--                    --><?php
        //                }
        //                ?>
        <!--                --><?php
        //                if (!empty($customer_location)) {
        //                    ?>
        <!--                    <tr>-->
        <!--                        <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer_location"); ?><!--</th>-->
        <!--                        <th style="width: 45%; text-align: right;">-->
        <?php //echo $customer_location; ?><!--</th>-->
        <!--                    </tr>-->
        <!--                    --><?php
        //                }
        //                ?>
        <!--                <tr>-->
        <!--                    <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer_discount"); ?><!--</th>-->
        <!--                    <th style="width: 45%; text-align: right;">-->
        <?php //echo $customer_discount_percent . ' %'; ?><!--</th>-->
        <!--                </tr>-->
        <!--                <tr>-->
        <!--                    <th style='width: 55%;'>-->
        <?php //echo lang("sales_lang.sales_customer_total"); ?><!--</th>-->
        <!--                    <th style="width: 45%; text-align: right;">-->
        <?php //echo to_currency($customer_total); ?><!--</th>-->
        <!--                </tr>-->
        <!--            </table>-->
        <!---->
        <!--            --><?php //echo anchor($controller_name . "/remove_customer", '<span class="glyphicon glyphicon-remove">&nbsp</span>' . lang('sales_lang.common_remove') . ' ' . lang('sales_lang.customers_customer'),
        //                array('class' => 'btn btn-danger btn-sm', 'id' => 'remove_customer_button', 'title' => lang('sales_lang.common_remove') . ' ' . lang('sales_lang.customers_customer'))); ?>
        <!--            --><?php
        //        } else {
        //            ?>
        <!--            --><?php //echo form_open($controller_name . "/select_customer", array('id' => 'select_customer_form', 'class' => 'form-horizontal')); ?>
        <!--            <div class="form-group" id="select_customer">-->
        <!--                <label id="customer_label" for="customer" class="control-label"-->
        <!--                       style="margin-bottom: 1em; margin-top: -1em;">-->
        <?php //echo lang('sales_lang.sales_select_customer'); ?><!--</label>-->
        <!--                --><?php //echo form_input(array('name' => 'customer', 'id' => 'customer', 'class' => 'form-control input-sm', 'value' => lang('sales_lang.sales_start_typing_customer_name'))); ?>
        <!---->
        <!--                <button class='btn btn-info btn-sm modal-dlg'-->
        <!--                        data-btn-submit='--><?php //echo lang('sales_lang.common_submit') ?><!--'-->
        <!--                        data-href='--><?php //echo site_url("customers/view"); ?><!--'-->
        <!--                        title='-->
        <?php //echo lang($sales_lang.controller_name . '_new_customer'); ?><!--'>-->
        <!--                    <span-->
        <!--                        class="glyphicon glyphicon-user">&nbsp</span>--><?php //echo lang($sales_lang.controller_name . '_new_customer'); ?>
        <!--                </button>-->
        <!---->
        <!--            </div>-->
        <!--            --><?php //echo form_close(); ?>
        <!--            --><?php
        //        }
        //        ?>


        <table class="sales_table_100" id="sale_totals">
            <tr>
                <th style="width: 55%;"><?php echo lang('sales_lang.sales_sub_total'); ?></th>
                <th style="width: 45%; text-align: right;"><?php echo to_currency($appData['tax_included'] ? $tax_exclusive_subtotal : $subtotal); ?></th>
            </tr>

            <?php

            foreach ($taxes as $name => $value) {
                ?>
                <tr>
                    <th style='width: 55%;'><?php echo $name; ?></th>
                    <th style="width: 45%; text-align: right;"><?php echo to_currency($value); ?></th>
                </tr>
                <?php
            }
            ?>

            <tr>
                <th style='width: 55%;'><?php echo lang('sales_lang.sales_total'); ?></th>
                <th style="width: 45%; text-align: right;"><?php echo to_currency($total); ?></th>
            </tr>
        </table>

        <?php
        // Only show this part if there are Items already in the sale.
        if (count($cart) > 0) {
            ?>
            <table class="sales_table_100" id="payment_totals">
                <tr>
                    <th style="width: 55%;"><?php echo lang('sales_lang.sales_payments_total'); ?></th>
                    <th style="width: 45%; text-align: right;"><?php echo to_currency($payments_total); ?></th>
                </tr>
                <tr>
                    <th style="width: 55%;"><?php echo lang('sales_lang.sales_amount_due'); ?></th>
                    <th style="width: 45%; text-align: right;"><?php echo to_currency($amount_due); ?></th>
                </tr>
            </table>


            <!--            --><?php //echo form_open($controller_name . "/cancel", array('id' => 'buttons_form')); ?>
            <!--            <div class="form-group" id="buttons_sale">-->
            <!--                <div class='btn btn-sm btn-default pull-left' id='suspend_sale_button'><span-->
            <!--                        class="glyphicon glyphicon-align-justify">&nbsp</span>--><?php //echo lang('sales_lang.sales_suspend_sale'); ?>
            <!--                </div>-->
            <!---->
            <!--                <div class='btn btn-sm btn-danger pull-right' id='cancel_sale_button'><span-->
            <!--                        class="glyphicon glyphicon-remove">&nbsp</span>--><?php //echo lang('sales_lang.sales_cancel_sale'); ?>
            <!--                </div>-->
            <!--            </div>-->
            <!--            --><?php //echo form_close(); ?>


            <?php
            // Only show this part if the payment cover the total
            if ($payments_cover_total) {
                ?>
                <div class="container-fluid">
                    <div class="no-gutter row">
                        <div class="form-group form-group-sm">
                            <div class="col-xs-12">
                                <?php echo form_label(lang('common_lang.common_comments'), 'comments', array('class' => 'control-label', 'id' => 'comment_label', 'for' => 'comment')); ?>
                                <?php echo form_textarea(array('name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm', 'value' => $comment, 'rows' => '2')); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display:none;">

                        <div class="form-group form-group-sm">
                            <div class="col-xs-6">
                                <label for="sales_print_after_sale" class="control-label checkbox">
                                    <?php $print_after_sale = 'checked'; ?>
                                    <?php echo form_checkbox(array('name' => 'sales_print_after_sale',
                                        'id' => 'sales_print_after_sale', 'value' => 1,
                                        'checked' => $print_after_sale)); ?>
                                    <?php echo lang('sales_lang.sales_print_after_sale') ?>
                                </label>
                            </div>

                            <?php
                            if (!empty($customer_email)) {
                                ?>
                                <div class="col-xs-6">
                                    <label for="email-receipt" class="control-label checkbox">
                                        <?php echo form_checkbox(array('name' => 'email_receipt', 'id' => 'email_receipt', 'value' => 1, 'checked' => $email_receipt)); ?>
                                        <?php echo lang('sales_lang.sales_email_receipt'); ?>
                                    </label>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    if ($mode == "sale" && $appData['invoice_enable'] == TRUE) {
                        ?>
                        <div class="row" style="display:none;">
                            <div class="form-group form-group-sm">
                                <div class="col-xs-6">
                                    <label class="control-label checkbox" for="sales_invoice_enable">
                                        <?php echo form_checkbox(array('name' => 'sales_invoice_enable', 'id' => 'sales_invoice_enable', 'value' => 1, 'checked' => $invoice_number_enabled)); ?>
                                        <?php echo lang('sales_lang.sales_invoice_enable'); ?>
                                    </label>
                                </div>

                                <div class="col-xs-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-addon input-sm">#</span>
                                        <?php echo form_input(array('name' => 'sales_invoice_number', 'id' => 'sales_invoice_number', 'class' => 'form-control input-sm', 'value' => $invoice_number)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
            <?php
        }
        ?>

    </div>
</div>