<?php $this->load->view("partial/header"); ?>

<script type="text/javascript">
    $(document).ready(function() {
        // when any filter is clicked and the dropdown window is closed
        <?php $this->load->view('partial/daterangepicker'); ?>

        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            table_support.refresh();
        });

        <?php $this->load->view('partial/bootstrap_tables_locale'); ?>
        table_support.init({
            resource: '<?php echo site_url($controller_name); ?>',
            headers: <?php echo $table_headers; ?>,
            // pageSize: <?php //echo $this->config->item('lines_per_page'); ?>,
            uniqueId: 'id',
            onLoadSuccess: function(response) {
                if ($("#table tbody tr").length > 1) {
                    /**
                     * hide extra elements in print
                     */
                    $('.search .input-sm').addClass('print_hide');
                    $('.keep-open').addClass('print_hide');
                    $('.export').addClass('print_hide');
                    $('.pagination-info').addClass('print_hide');
                    $('.fixed-table-pagination').addClass('print_hide');

                    /**
                     * Update Report Heading
                     */
                    var reportHeading = "";
                    // var filters = $('#filters').val();
                    // if (filters && filters.length) {
                    //     reportHeading = " Payment Type: " +
                    //         $('#filters').val()[0] + " ";
                    // } else {
                    //     reportHeading = " All Payment Types ";
                    // }

                    reportHeading += "DELETED SALES LOG REPORT"+
                        "<br/>Date: " +
                        $('#daterangepicker').val();

                    $('#report_heading').html(reportHeading);

                    $('.pagination-info').addClass('print_hide');
                }
            },
            queryParams: function() {
                return $.extend(arguments[0], {
                    start_date: start_date,
                    end_date: end_date,
                    branch_code: $("#branch_code").val(),
                });
            },
            columns: {
                'invoice': {
                    align: 'center'
                }
            }
        });


        $(document).ajaxComplete(function() {
            $('#status_msg').hide();
        });

        $(document).ajaxStart(function() {
            $('#status_msg').show();
        });



        //        $('#btnRefreshReport').click(function(e){
        //            refreshReportTable();
        //        });
        //
        //        function refreshReportTable()
        //        {
        //            table_support.refresh();
        //        }

    });
</script>

<?php $this->load->view('partial/print_receipt', array('print_after_sale' => false, 'selected_printer' => 'takings_printer')); ?>
<div id="title_bar" class="print_hide btn-toolbar">
    <div class="inner_block">
        <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
            <span class="glyphicon glyphicon-print">&nbsp</span><?php echo $this->lang->line('common_print'); ?>
        </button>
        <?php echo anchor("sales", '<span class="glyphicon glyphicon-shopping-cart">&nbsp</span>' . $this->lang->line('sales_register'), array('class' => 'btn btn-info btn-sm pull-right', 'id' => 'show_sales_button')); ?>
    </div>
</div>

<div id="receipt_header" class="normal_hide">
    <?php
    if ($this->Appconfig->get('company_logo') != '') {
    ?>
        <div id="company_name"><img id="image" src="<?php echo base_url('uploads/' . $this->Appconfig->get('company_logo')); ?>" alt="company_logo" /></div>
    <?php
    }
    ?>

    <!--        <div id="company_name">--><?php //echo $this->config->item('company'); 
                                            ?>
    <!--</div>-->

    <?php
    $branch_code = $this->gu->getStoreBranchCode();
    $store = $this->gu->getStoreInfoByBranchCode($branch_code);

    $branches = $this->gu->getBranches();

    $address = $store['address'];
    $phone = $store['phone'];
    ?>

    <div id="company_address"><?php echo $address; ?></div>
    <div id="company_phone"><?php echo $phone; ?></div>

</div>
<div class="inner_block">
    <h5 id="report_heading" style="font-weight: bold; line-height: 25px;">DELETED SALES LOG REPORT</h5>

    <div id="toolbar" class="print_hide">
        <code id="status_msg" style="font-weight: bolder;">fetching report...</code>
        <div class="pull-left form-inline" role="toolbar">

            <?php echo "<input type='hidden' id='branch_code' value='".$branch_code."'/>"; ?>

            <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
       
        </div>
    </div>

    <div id="table_holder">
        <table id="table"></table>
    </div>

  
</div>

<?php $this->load->view("partial/footer"); ?>