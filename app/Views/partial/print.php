<script type="text/javascript">
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
                    var default_ticket_printer = window.localStorage && localStorage['<?php echo $selected_printer; ?>'];
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
            //window.location.href = "<?php //echo base_url('sales'); ?>";
        }
    }
</script>