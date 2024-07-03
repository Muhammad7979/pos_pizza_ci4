<?php include(APPPATH . 'Views/partial/header.php'); ?>

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
    <?php if($gu->isServer()): ?>
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                    <h3 class="panel-title"><span
                                class="glyphicon glyphicon-stats">&nbsp</span><?php echo lang('reports_lang.reports_graphical_reports'); ?>
                        </h3>
                    </div>
                  <div class="list-group">
                    <?php
                   foreach ($grants as $grant) {
                    if (!preg_match('/reports_(inventory|receivings)/', $grant['permission_id']) && strpos($grant['permission_id'], 'reports_') !== false) {
                        // Call the show_report function passing the necessary parameters
                        show_report('graphical_summary', $grant['permission_id']);
                    }
                    }
                    ?>
                   </div>
                </div>
            </div>

            <?php endif; ?>



            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                    <h3 class="panel-title"><span
                                class="glyphicon glyphicon-stats">&nbsp</span><?php echo lang('reports_lang.reports_summary_reports'); ?>
                        </h3>
                    </div>
                    <div class="list-group">
                        <?php
                       foreach ($grants as $grant) {
                        if (!preg_match('/reports_(inventory|receivings)/', $grant['permission_id']) && strpos($grant['permission_id'], 'reports_') !== false) {
                            // Call the show_report function passing the necessary parameters
                            show_report('summary', $grant['permission_id']);
                        }
                        }
                        ?>
                        
                    </div>
                </div>
            </div>
<!-- -----------------------//Detail Report//----------------------------------------------- -->
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><span
                                class="glyphicon glyphicon-list-alt">&nbsp</span><?php echo lang('reports_lang.reports_detailed_reports'); ?>
                        </h3>
                    </div>
                    <div class="list-group">
                        <?php
                            $person_id = session()->get('person_id');
                            if(emp_have_grant('reports_sales_for_directors', $person_id)):
                        ?>
                            <a class="list-group-item" href="<?php echo base_url('sales/takings'); ?>">Sale Takings For Directors</a>
                        <?php
                            endif;
                            if(emp_have_grant('sales', $person_id)):
                        ?>
                            <a class="list-group-item" href="<?php echo base_url('sales/manage'); ?>">Sale Takings</a>
                        <?php
                            endif;
                            if($gu->isServer()){
                                show_report_if_allowed('detailed', 'sales', $person_id);
                                show_report_if_allowed('detailed', 'receivings', $person_id);
                                show_report_if_allowed('specific', 'customer', $person_id, 'reports_customers');
                                show_report_if_allowed('specific', 'discount', $person_id, 'reports_discounts');
                                show_report_if_allowed('specific', 'employee', $person_id, 'reports_employees');
                            }
                        ?>
                    </div>
                </div>

                <?php
                if ($empModel->has_grant('reports_inventory', session()->get('person_id'))) {
                    ?>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title"><span
                                    class="glyphicon glyphicon-book">&nbsp</span><?php echo lang('reports_lang.reports_inventory_reports'); ?>
                            </h3>
                        </div>
                        <div class="list-group">
                            <?php
                            show_report('', 'reports_inventory_low');
                            show_report('', 'reports_inventory_summary');
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>





</div>




</div>
<?php include(APPPATH . 'Views/partial/footer.php'); ?>