<?php  include(APPPATH . "Views/partial/header.php"); ?>

<?php
if (isset($error_message)) {
    echo "<div class='alert alert-dismissible alert-danger'>" . $error_message . "</div>";
    exit;
}
?>

<?php if (!empty($customer_email)): ?>
    <script type="text/javascript">
        $(document).ready(function () {
            var send_email = function () {
                $.get('<?php echo base_url() . "/sales/send_receipt/" . $sale_id_num; ?>',
                    function (response) {
                        $.notify(response.message, {type: response.success ? 'success' : 'danger'});
                    }, 'json'
                );
            };

            $("#show_email_button").click(send_email);

            <?php if(!empty($email_receipt)): ?>
            send_email();
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<?php
if (service('router')->methodName() == "complete"):
?>

<div class="guLoader120px center-block print_hide"></div>

<div class="inner_block normal_hide">
    <?php
    else:
    ?>
    <div class="inner_block">
        <?php
        endif; ?>
        <?php  view('partial/print_receipt', array('print_after_sale' => $print_after_sale, 'selected_printer' => 'receipt_printer')); ?>

        <div class="print_hide" id="control_buttons" style="text-align:right">
            <a href="javascript:printdoc();">
                <div class="btn btn-info btn-sm" ,
                     id="show_print_button"><?php echo '<span class="glyphicon glyphicon-print">&nbsp</span>' . lang('common_lang.common_print'); ?></div>
            </a>
            <?php /* this line will allow to print and go back to sales automatically.... echo anchor("sales", '<span class="glyphicon glyphicon-print">&nbsp</span>' . lang('common_lang.common_print'), array('class'=>'btn btn-info btn-sm', 'id'=>'show_print_button', 'onclick'=>'window.print();')); */ ?>
            <?php if (isset($customer_email) && !empty($customer_email)): ?>
                <a href="javascript:void(0);">
                    <div class="btn btn-info btn-sm" ,
                         id="show_email_button"><?php echo '<span class="glyphicon glyphicon-envelope">&nbsp</span>' . lang('sales_lang.sales_send_receipt'); ?></div>
                </a>
            <?php endif; ?>
            <?php echo anchor('sales', '<span class="glyphicon glyphicon-shopping-cart">&nbsp</span>' . lang('sales_lang.sales_register'), array('class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button')); ?>
            <?php echo anchor("sales/manage", '<span class="glyphicon glyphicon-list-alt">&nbsp</span>' . lang('sales_lang.sales_takings'), array('class' => 'btn btn-info btn-sm', 'id' => 'show_takings_button')); ?>
        </div>

          <?php include(APPPATH.'Views/sales/'.$appData['receipt_template'].'.php');?> 
    </div>

    <?php  include(APPPATH . "Views/partial/footer.php"); ?>
    <script>
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
        // $(document).ready(function(){
        <?php if(service('router')->methodName() == "complete"): ?>
        printdoc();
        <?php endif; ?>
        // });
    </script>



