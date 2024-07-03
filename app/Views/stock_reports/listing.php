<?php echo view("partial/header"); ?>

    <div id="title_bar" class="btn-toolbar print_hide">
        <div class="inner_block">
            <div id="page_title">
                Choose Report
                <a href="<?php echo base_url('') ; ?>"
                   class="back_link" >Back</a>
            </div>
        </div>
    </div>

    <br/><br/>
    <div class="inner_block">
        <?php
        if (isset($error)) {
            echo "<div class='alert alert-dismissible alert-danger'>" . $error . "</div>";
        }
        ?>

        <div class="row">

            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><span
                                class="glyphicon glyphicon-list">&nbsp</span><?php echo lang('reports_lang.reports_inventory_reports'); ?>
                        </h3>
                    </div>
                    <div class="list-group">
                        <?php

                            //show_report('inventoryi', 'reports_warehouse_stock');
                            //show_report('inventoryi', 'reports_store_stock');
                            show_report_if_allowed('inventoryi', 'order_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'warehouse_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'store_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'counter_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'vendor_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'processing_stock', $person_id);
                            show_report_if_allowed('inventoryi', 'counter_item', $person_id);
                            
                            show_report_if_allowed('inventoryi', 'pizza_stock', $person_id);
                            
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?php echo view("partial/footer"); ?>