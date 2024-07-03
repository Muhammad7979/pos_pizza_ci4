<?php include(APPPATH . 'Views/partial/header.php'); ?>


<script type="text/javascript">
    $(document).ready(function () {
        // when any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function (e) {
            table_support.refresh();
        });
        $('#sale_mode').on('hidden.bs.select', function (e) {
            table_support.refresh();
        });
        $('#branch_code').on('hidden.bs.select', function (e) {
            table_support.refresh();
        });

//        $('#branch_code').keyup(function(e){
//            table_support.refresh();
//        });

        // load the preset datarange picker
        <?php  include(APPPATH . 'Views/partial/daterangepicker.php'); ?>

        $("#daterangepicker").on('apply.daterangepicker', function (ev, picker) {
            table_support.refresh();
        });
        <?php  include(APPPATH . 'Views/partial/bootstrap_tables_locale.php');?>

        table_support.init({
            resource: '<?php echo base_url($controller_name);?>',
            headers: <?php echo $table_headers; ?>,
            pageSize: <?php echo $appData['lines_per_page']; ?>,
            uniqueId: 'sale_id',
            onLoadSuccess: function (response) {
                console.log(response);
                if ($("#table tbody tr").length > 1) {
                    $("#payment_summary").html(response.payment_summary);
                    $("#table tbody tr:last td:first").html("");

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
                    var filters = $('#filters').val();
                    if(filters && filters.length){
                        reportHeading = " Payment Type: "+
                        $('#filters').val()[0] + " ";
                    }
                    else{
                        reportHeading = " All Payment Types ";
                    }

                    reportHeading += " <br/>Sale Type: "+
                        $('#sale_mode').val() +
                        "<br/>Date: "+
                        $('#daterangepicker').val();

                    $('#report_heading').html(reportHeading);

                    $('.pagination-info').addClass('print_hide');
                }
            },
            queryParams: function () {
                return $.extend(arguments[0], {
                    start_date: start_date,
                    end_date: end_date,
                    sale_mode: $("#sale_mode").val(),
                    branch_code: $("#branch_code").val(),
                    filters: $("#filters").val() || [""]
                });
            },
            columns: {
                'invoice': {
                    align: 'center'
                }
            }
        });


        $( document ).ajaxComplete(function() {
            $('#status_msg').hide();
        });

        $( document ).ajaxStart(function() {
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


<?php  include(APPPATH.'Views/partial/print_receipt.php'); ?>
<div id="title_bar" class="print_hide btn-toolbar">
    <div class="inner_block">
        <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
            <span class="glyphicon glyphicon-print">&nbsp</span><?php echo lang('common_lang.common_print'); ?>
        </button>
        <?php echo anchor("sales", '<span class="glyphicon glyphicon-shopping-cart">&nbsp</span>' . lang('sales_lang.sales_register'), array('class' => 'btn btn-info btn-sm pull-right', 'id' => 'show_sales_button')); ?>
    </div>
</div>

<div id="receipt_header" class="normal_hide">
    <?php
    if ($appData['company_logo'] != '') {
        ?>
        <div id="company_name"><img id="image"
                                    src="<?php echo base_url('uploads/' . $appData['company_logo']); ?>"
                                    alt="company_logo"/></div>
        <?php
    }
    ?>

    <!--        <div id="company_name">--><?php //echo $this->config->item('company'); ?><!--</div>-->

    <?php
    $branch_code = $gu->getStoreBranchCode();
    $store = $gu->getStoreInfoByBranchCode($branch_code);

    $branches = $gu->getBranches();

    $address = $store['address'];
    $phone = $store['phone'];
    ?>

    <div id="company_address"><?php echo $address; ?></div>
    <div id="company_phone"><?php echo $phone; ?></div>

    <div>SALES REPORT</div>
</div>

<div class="inner_block">
    <h5 id="report_heading" style="font-weight: bold; line-height: 25px;">Sales Report</h5>

    <div id="toolbar" class="print_hide">
<!--        <button id="btnRefreshReport" class="button">Refresh</button>-->
        <code id="status_msg" style="font-weight: bolder;">fetching report...</code>
        <div class="pull-left form-inline" role="toolbar">
            &nbsp;<button id="delete" class="btn btn-default btn-sm print_hide">
                <span class="glyphicon glyphicon-trash">&nbsp;</span><?php echo lang("common_lang.common_delete"); ?>
            </button>&nbsp;

            <?php
            if($gu->isServer()){
//                echo form_input(array('name' => 'branch_code',
//                    'placeholder' => 'Branch Code', 'value' => '',
//                    'style' => 'max-width:100px;',
//                    'class' => 'form-control input-sm', 'id' => 'branch_code'));

             echo form_dropdown('branch_code', $branches, '',
                array('id' => 'branch_code',
                    'data-none-selected-text' => 'Filter by Branch',
                    'class' => 'selectpicker show-menu-arrow',
                    'data-selected-text-format' => 'count > 1',
                    'data-style' => 'btn-default btn-sm',
                    'data-width' => 'fit'));
            }
            else{
                echo "<input type='hidden' id='branch_code' value='".$branch_code."'/>";
            }

            ?>


            <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
            <?php echo form_multiselect('filters[]', $filters, [],
                array('id' => 'filters',
                'data-none-selected-text' => 'All Payment Types',
                'class' => 'selectpicker show-menu-arrow',
                'data-selected-text-format' => 'count > 1',
                'data-style' => 'btn-default btn-sm',
                'data-width' => 'fit')); ?>

            <?php echo form_dropdown('sale_mode', $sale_modes, '',
                array('id' => 'sale_mode',
                    'data-none-selected-text' => lang('common_lang.common_none_selected_text'),
                    'class' => 'selectpicker show-menu-arrow',
                    'data-selected-text-format' => 'count > 1',
                    'data-style' => 'btn-default btn-sm',
                    'data-width' => 'fit'));  ?>
        </div>
    </div>

    <div id="table_holder">
        <table id="table"></table>
    </div>

    <div id="payment_summary">
    </div>
</div>
<?php  include(APPPATH . 'Views/partial/footer.php'); ?>
