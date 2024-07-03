                
<?php

 if (isset($bill_print) || session()->get('last_receipt') || isset($receipt_view)) {
    if (isset($bill_print) && $bill_print == false) {
        if (session()->get('last_receipt')) {
            extract(session()->get('last_receipt'));
            $print = true;
        } else {
            $print = false;

        }
    } else {
        $print = true;
    }

    if ($print) {
?>
        <div id="receipt_wrapper">
            <div id="receipt_header">
                <?php
                if ($appData['company_logo'] != '') {
                ?>
                    <div id="company_name"><img id="image" src="<?php echo base_url('uploads/' . $appData['company_logo']); ?>" alt="company_logo" /></div>
                <?php
                }
                ?>

                <!--        <div id="company_name">--><?php //echo $this->config->item('company');
                                                        ?>
                <!--</div>-->

                <?php
                $address = $appData['address'];
                $phone = $appData['phone'];
                // $strn = $appData['strn'];
                // $ntn = $appData['ntn'];
                ?>

                <div id="company_address"><?php echo $address; ?></div>
                <div id="company_phone"><?php echo $phone; ?></div>

                <!-- <div id="tax" style="padding-bottom: 10px">
                    <table style="margin: 0 auto; width: 90%;">
                        <tr>
                            <td class="text-left">STRN # <?php // echo $strn; ?></td>
                            <td class="text-right">NTN # <?php // echo $ntn; ?></td>
                        </tr>
                    </table>

                </div> -->
                <div id="sale_receipt">
                    <?php
                    if (service('router')->methodName() == "index") {
                        echo "Duplicate Bill";
                    } else {
                        echo $receipt_title;
                    }
                    ?>
                </div>

                <?php
                if ($sale_mode != "normal") :
                ?>
                    <br />
                    <div>Register: <?php echo ucwords($sale_mode); ?></div>
                    <br />
                <?php endif; ?>

                <div id="sale_time"><?php echo $transaction_time ?></div>

            </div>

            <div id="receipt_general_info">
                <?php
                if (isset($customer)) {
                ?>
                    <div id="customer"><?php echo lang('customers_lang.customers_customer') . ": " . $customer; ?></div>
                <?php
                }
                ?>

                <!--		<div id="sale_id">--><?php //echo lang('sales_lang.sales_id').": ".$sale_id;
                                                    ?>
                <!--</div>-->

                <?php
                if (!empty($invoice_number)) {
                ?>
                    <!--            <div-->
                    <!--                id="invoice_number">--><?php //echo "Sale ID: " . $invoice_number; 
                                                                ?>
                    <!--</div>-->
                <?php
                }
                ?>

                <div id="employee"><?php echo
                                    lang('employees_lang.employees_employee') . ": "
                                        . $gu->getInitials($employee); ?></div>
                <div>Payment: <?php echo $payment_type == 'Cod' ? 'Cash' : $payment_type; ?></div>
            </div>

            <table id="receipt_items" border="2px solid black" style="margin-bottom: 0px;">
                <thead>
                    <tr style="border-bottom: 1px solid black;">
                        <th style="width:5%;">&nbsp;#</th>
                        <th style="width:55%;">Description</th>
                        <th style="width:10%;">Rate</th>
                        <!-- <th style="width:10%;">GST %</th> -->
                        <th style="width:10%;">&nbsp;<?php echo lang('sales_lang.sales_quantity'); ?></th>
                        <th colspan="4" style="width:20%;border-right: 2px solid black;" class="total-value"><?php echo lang('sales_lang.sales_total'); ?>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach (array_reverse($cart, true) as $line => $item) {
                    ?>
                        <tr>
                            <td><?php echo $i++; ?>.</td>
                            <td><?php echo ucfirst($item['name']); ?></td>
                            <td><?php echo to_currency($item['cost_price']); ?></td>
                            <!-- <td><?php //echo to_tax_decimals($item['item_tax_percent'] ? $item['item_tax_percent'] : 0); ?></td> -->
                            <td><?php echo to_quantity_decimals($item['quantity']); ?></td>
                            <td class="total-value" style="border-right: 2px solid black;"><?php echo to_currency($item[($appData['receipt_show_total_discount'] ? 'total' : 'discounted_total')]); ?></td>
                        </tr>
                        <?php
                        if ($appData['receipt_show_description'] || $appData['receipt_show_serialnumber']) :
                        ?>
                            <tr>
                                <?php
                                if ($appData['receipt_show_description']) {
                                ?>
                                    <td colspan="2"><?php echo $item['description']; ?></td>
                                <?php
                                }
                                ?>
                                <?php
                                if ($appData['receipt_show_serialnumber']) {
                                ?>
                                    <td><?php echo $item['serialnumber']; ?></td>
                                <?php
                                }
                                ?>
                            </tr>
                        <?php
                        endif;
                        ?>
                        <?php
                        if ($item['discount'] > 0) {
                        ?>
                            <tr>
                                <td colspan="4" class="discount"><?php echo number_format($item['discount'], 0) . " " . lang("sales_lang.sales_discount_included") ?></td>
                                <td class="total-value"><?php echo to_currency($item['discounted_total']); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    <?php
                    }
                    ?>

                    <?php
                    if ($appData['receipt_show_total_discount'] && $discount > 0) {
                    ?>
                        <tr>
                            <td colspan="4" style='text-align:right;border-top:2px solid #000000;'><?php echo lang('sales_lang.sales_sub_total'); ?></td>
                            <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($subtotal); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="total-value"><?php echo lang('sales_lang.sales_discount'); ?>:</td>
                            <td class="total-value"><?php echo to_currency($discount * -1); ?></td>
                        </tr>
                    <?php
                    }
                    ?>

                    <?php
                    if ($appData['receipt_show_taxes']) {
                    ?>
                        <tr>
                            <td colspan="4" style='text-align:right;border-top:2px solid #000000;'><?php echo lang('sales_lang.sales_sub_total'); ?></td>
                            <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($appData['tax_included'] ? $tax_exclusive_subtotal : $discounted_subtotal); ?></td>
                        </tr>
                        <?php
                        foreach ($taxes as $name => $value) {
                        ?>
                            <tr>
                                <td colspan="4" class="total-value"><?php echo $name; ?>:</td>
                                <td class="total-value"><?php echo to_currency($value); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    <?php
                    }
                    ?>

                    <tr>
                    </tr>

                    <?php $border = (!$appData['receipt_show_taxes'] && !($appData['receipt_show_total_discount'] && $discount > 0)); ?>
                    <?php
                    if ($fbr_fee && $fbr_fee != 0) {
                    ?>
                        <tr>
                            <td colspan="4" style="text-align:right; <?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo lang('config_lang.fbr_fee'); ?></td>
                            <td style="text-align:right; border-right: 2px solid black;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo to_currency($fbr_fee); ?></td>
                        </tr>
                    <?php
                    }
                    ?>

                    <tr>
                        <td colspan="4" style="text-align:right; <?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo lang('sales_lang.sales_total'); ?></td>
                        <td style="text-align:right; border-right: 2px solid black;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo to_currency($total); ?></td>
                    </tr>


                    <?php
                    $only_sale_check = FALSE;
                    $show_giftcard_remainder = FALSE;
                    foreach ($payments as $payment_id => $payment) {
                        $only_sale_check |= $payment['payment_type'] == lang('sales_lang.sales_check');
                        $splitpayment = explode(':', $payment['payment_type']);
                        $show_giftcard_remainder |= $splitpayment[0] == lang('sales_lang.sales_giftcard');
                    ?>
                        <tr>
                            <td colspan="4" style="text-align:right;"><?php echo $splitpayment[0] == 'Cod' ? 'Cash' : $splitpayment[0]; ?> </td>
                            <td class="total-value" style="border-right: 2px solid black;"><?php echo to_currency($payment['payment_amount'] * 1); ?></td>
                        </tr>
                    <?php
                    }
                    ?>

                    <!-- <?php
                            //if (isset($cur_giftcard_value) && $show_giftcard_remainder) {
                            ?>
                    <tr>
                        <td colspan="5"
                            style="text-align:right;"><?php //echo lang('sales_lang.sales_giftcard_balance'); 
                                                        ?></td>
                        <td class="total-value"
                            style="border-right: 2px solid black;"><?php //echo to_currency($cur_giftcard_value); 
                                                                    ?></td>
                    </tr>
                    <?php
                    //}
                    ?> -->
                    <tr>
                        <td colspan="4" style="text-align:right;"> <?php echo lang($amount_change >= 0 ? ($only_sale_check ? 'sales_lang.sales_check_balance' : 'sales_lang.sales_change_due') : 'sales_lang.sales_amount_due'); ?> </td>
                        <td class="total-value" style="border-right: 2px solid black;"><?php echo to_currency($amount_change); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if ($barcode) : ?>
                <div id="barcode" style="border: 2px solid; border-top: 0px; margin-top: 0px; padding-top: 10px">
                    <img src='data:image/png;base64,<?php echo $barcode; ?>' /><br>
                    SR# <?php echo $bill_number; ?>
                </div>
            <?php endif; ?>

            <div id="sale_return_policy" style="border: 2px solid; border-top: 0px;">
                <table style="margin: 0 auto; width: 100%;">
                    <tr>
                        <td class="text-center">
                            <?php echo nl2br($appData['return_policy']); ?>
                        </td>
                        <?php

                         if ($fbr_qrcode) : ?>
                            <td class="text-center" style="border-left: 2px solid; padding: 10px;">
                                <img src='<?php echo base_url('uploads/qr_image/' . $fbr_qrcode); ?>' />
                                <br>
                                <?php echo nl2br(substr($fbr_qrcode, 0, -4)); ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>


            

            
            <!-- //////////////////////////// For suspend bill reciept ///////////////////////////////// -->

           <?php if($paid_cart !=null){ 
   
   foreach($paid_cart as $paid){
  ?>
  



  <div id="receipt_header" style="margin-top:2%">

    <div id="sale_receipt">
          <?php
          if (service('router')->methodName() == "index") {
              echo "Previous Paid Duplicate Bill";
          } else {
              echo "Previous paid bill";
          }
          ?>
      </div>
      <?php
      if ($sale_mode != "normal") :
      ?>
          <br />
          <div>Register: <?php echo ucwords($sale_mode); ?></div>
          <br />
      <?php endif; ?>

      <div id="sale_time"><?php echo $paid['transaction_time'] ?></div>
      </div>
<table id="receipt_items" border="2px solid black" style="margin-bottom: 0px;">
      <thead>
          <tr style="border-bottom: 1px solid black;">
              <th style="width:5%;">&nbsp;#</th>
              <th style="width:55%;">Description</th>
              <th style="width:10%;">Rate</th>
              <!-- <th style="width:10%;">GST %</th> -->
              <th style="width:10%;">&nbsp;<?php echo lang('sales_lang.sales_quantity'); ?></th>
              <th colspan="4" style="width:20%;border-right: 2px solid black;" class="total-value"><?php echo lang('sales_lang.sales_total'); ?>&nbsp;</th>
          </tr>
      </thead>
      <tbody>
          <?php
          $j = 1;
          $paid_total = 0;
          $paid_subtotal = 0;

          foreach(array_reverse($paid['cart'], true) as $line => $cart_item) {

          $item_total = 0;
          $paid_total += $cart_item['price']*$cart_item['quantity'];
          $item_total = $cart_item['price']*$cart_item['quantity'];
          $paid_subtotal += $cart_item['cost_price']*$cart_item['quantity'];

          ?>
              <tr>
                  <td><?php echo $j++; ?>.</td>
                  <td><?php echo ucfirst($cart_item['name']); ?></td>
                  <td><?php echo to_currency($cart_item['cost_price']); ?></td>
                  <!-- <td><?php //echo to_tax_decimals($cart_item['cart_item_tax_percent'] ? $cart_item['cart_item_tax_percent'] : 0); ?></td> -->
                  <td><?php echo to_quantity_decimals($cart_item['quantity']); ?></td>
                  <td class="total-value" style="border-right: 2px solid black;">
                      <?php echo to_currency($cart_item['price']); ?>
                      </td>
              </tr>
              <?php
              if ($appData['receipt_show_description'] || $appData['receipt_show_serialnumber']) :
              ?>
                  <tr>
                      <?php
                      if ($appData['receipt_show_description']) {
                      ?>
                          <td colspan="2"><?php echo $cart_item['description']; ?></td>
                      <?php
                      }
                      ?>
                      <?php
                      if ($appData['receipt_show_serialnumber']) {
                      ?>
                          <td><?php echo $cart_item['serialnumber']; ?></td>
                      <?php
                      }
                      ?>
                  </tr>
              <?php
              endif;
              ?>
            <!--   <?php
             // if ($item['discount'] > 0) {
              ?>
                  <tr>
                      <td colspan="4" class="discount"><?php // echo number_format($item['discount'], 0) . " " . $this->lang->line("sales_discount_included") ?></td>
                      <td class="total-value"><?php // echo to_currency($item['discounted_total']); ?></td>
                  </tr>
              <?php
            //  }
              ?> -->
          <?php
          }
          ?>

          <?php
          if ($appData['receipt_show_total_discount'] && $discount > 0) {
          ?>
              <tr>
                  <td colspan="4" style='text-align:right;border-top:2px solid #000000;'><?php echo lang('sales_lang.sales_sub_total'); ?></td>
                  <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($paid['tax_exclusive_subtotal']); ?></td>
              </tr>
              <tr>
                  <td colspan="4" class="total-value"><?php echo lang('sales_lang.sales_discount'); ?>:</td>
                  <td class="total-value"><?php echo to_currency($discount * -1); ?></td>
              </tr>
          <?php
          }
          ?>

          <?php
          if ($appData['receipt_show_taxes']) {
          ?>
              <tr>
                  <td colspan="4" style='text-align:right;border-top:2px solid #000000;'><?php echo lang('sales_lang.sales_sub_total'); ?></td>
                  <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($appData['tax_included'] ? $paid['tax_exclusive_subtotal'] : $paid['tax_exclusive_subtotal']); ?></td>
              </tr>
              <?php
              foreach ($paid['taxes'] as $name => $value) {
              ?>
                  <tr>
                      <td colspan="4" class="total-value"><?php echo $name; ?>:</td>
                      <td class="total-value"><?php echo to_currency($value); ?></td>
                  </tr>
              <?php
              }
              ?>
          <?php
          }
          ?>

          <tr>
          </tr>

          <?php $border = (!$appData['receipt_show_taxes'] && !($appData['receipt_show_total_discount'] && $discount > 0)); ?>
          <?php
          if ($fbr_fee && $fbr_fee != 0) {
          ?>
              <tr>
                  <td colspan="4" style="text-align:right; <?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo  lang('config_lang.fbr_fee'); ?></td>
                  <td style="text-align:right; border-right: 2px solid black;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo to_currency($fbr_fee); ?></td>
              </tr>
          <?php
          }
          ?>

          <tr>
              <td colspan="4" style="text-align:right; <?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo lang('sales_lang.sales_total'); ?></td>
              <td style="text-align:right; border-right: 2px solid black;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo to_currency($paid['subtotal']+$fbr_fee); ?></td>
          </tr>


          <?php
          $only_sale_check = FALSE;
          $show_giftcard_remainder = FALSE;
          foreach ($paid['payments'] as $payment_id => $payment) {

              $only_sale_check |= $payment['payment_type'] == lang('sales_lang.sales_check');
              $splitpayment = explode(':', $payment['payment_type']);
              $show_giftcard_remainder |= $splitpayment[0] == lang('sales_lang.sales_giftcard');
          ?>
              <tr>
                  <td colspan="4" style="text-align:right;"><?php echo $splitpayment[0] == 'Cod' ? 'Cash' : $splitpayment[0]; ?> </td>
                  <td class="total-value" style="border-right: 2px solid black;"><?php echo to_currency($payment['payment_amount']); ?></td>
              </tr>
          <?php
          }
          ?>

          <!-- <?php
                  //if (isset($cur_giftcard_value) && $show_giftcard_remainder) {
                  ?>
          <tr>
              <td colspan="4"
                  style="text-align:right;"><?php //echo $this->lang->line('sales_giftcard_balance'); 
                                              ?></td>
              <td class="total-value"
                  style="border-right: 2px solid black;"><?php //echo to_currency($cur_giftcard_value); 
                                                          ?></td>
          </tr>
          <?php
          //}
          ?> -->
          <tr>
              <td colspan="4" style="text-align:right;"> <?php echo lang($amount_change >= 0 ? ($only_sale_check ? 'sales_lang.sales_check_balance' : 'sales_lang.sales_change_due') : 'sales_lang.sales_amount_due'); ?> </td>
              <td class="total-value" style="border-right: 2px solid black;"><?php echo to_currency($paid['amount_change']); ?></td>
          </tr>
      </tbody>
  </table>

  <?php if ($paid['barcode']) : ?>
      <div id="barcode" style="border: 2px solid; border-top: 0px; margin-top: 0px; padding-top: 10px">
          <img src='data:image/png;base64,<?php echo $paid['barcode']; ?>' /><br>
          SR# <?php echo $paid['invoice_number'] ?>
      </div>
  <?php endif; ?>

  <div id="sale_return_policy" style="border: 2px solid; border-top: 0px;">
      <table style="margin: 0 auto; width: 100%;">
          <tr>
              <td class="text-center">
                  <?php echo nl2br($appData['return_policy']); ?>
              </td>
              <?php if ($paid['fbr_qrcode']) : ?>
                  <td class="text-center" style="border-left: 2px solid; padding: 10px;">
                      <img src='<?php echo base_url('uploads/qr_image/' . $paid['fbr_qrcode']); ?>' />
                      <br>
                      <?php echo nl2br(substr($paid['fbr_qrcode'], 0, -4)); ?>
                  </td>
              <?php endif; ?>
          </tr>
      </table>
  </div>


  <?php
}

   }
    ?>









        </div>

<?php }
} ?>