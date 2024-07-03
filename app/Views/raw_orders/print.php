<?php echo view("partial/header"); ?>

    <script type="text/javascript">
        $(document).ready(function () {
            <?php echo view('partial/bootstrap_tables_locale'); ?>
            table_support.init({
                resource: '<?php echo site_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'order_id',
                onLoadSuccess: function (response) {
                    $('.export').addClass('hide');
                    if ($("#table tbody tr").length > 1) {

                        /**
                         * hide extra elements in print
                         */
                        $('.search .input-sm').addClass('print_hide');
                        $('.keep-open').addClass('print_hide');
                        $('.export').addClass('print_hide');
                        $('.pagination-info').addClass('print_hide');
                        $('.fixed-table-pagination').addClass('print_hide');
                        $('.pagination-info').addClass('print_hide');
                    }
                },
                queryParams: function() {
                    return $.extend(arguments[0], {
                        order_id: '<?php echo !empty($order_id) ? $order_id  : -1 ?>',
                        print_order: true,
                        category: <?php echo !empty($category) ? $category  : -1 ?>,
                    });
                },
            });
        });

    </script>

    <?php echo view('partial/print', array('selected_printer' => 'takings_printer')); ?>
    <div id="title_bar" class="print_hide btn-toolbar">
        <div class="inner_block">
            <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
                <span class="glyphicon glyphicon-print">&nbsp</span><?php echo lang('common_lang.common_print'); ?>
            </button>
        </div>
    </div>


    <div id="receipt_header" class="">
        <?php
        if ($appData['company_logo'] != '') {
            ?>
            <div id="company_name"><img id="image"
                                        src="<?php echo base_url('uploads/' . $appData['company_logo']); ?>"
                                        alt="company_logo"/></div>
            <?php
        }
        ?>

        <?php
        $branch_code = $gu->getStoreBranchCode();
        $store = $gu->getStoreInfoByBranchCode($branch_code);

        $branches = $gu->getBranches();

        $address = $store['address'];
        $phone = $store['phone'];
        ?>

        <div id="company_address"><?php echo $address; ?></div>
        <div id="company_phone"><?php echo $phone; ?></div>

    </div>

    <div class="jumbotron" style="max-width: 60%; margin:auto">
            <fieldset>
                <legend style="text-align: center;"><?php echo lang('raw_order_lang.raw_orders_details'); ?></legend>
                <div class="col-xs-12">
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_date'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo $raw_order_info->created_at ?></span>
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_status'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo $raw_order_info->order_status ?></span>
                </div>

                <div class="col-xs-12">
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_order_from'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo $order_from ?></span>
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_order_to'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo $order_to ?></span>
                </div>

                <div class="col-xs-12">
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_type'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo ($raw_order_info->category==1 || $raw_order_info->category==2) ? ($raw_order_info->category==1) ? 'Vendor' : 'Warehouse' : 'Store' ?></span>
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_quantity'); ?> :</label>
                    <span class="col-xs-3 control-label"><?php echo $total_order.' ('.parse_decimals($raw_order_info->order_quantity) .')' ?></span>
                </div>

                <div class="col-xs-12" style="margin-top: 20px;">
                    <label class="col-xs-3 control-label required"><?php echo lang('raw_order_lang.raw_orders_description'); ?> :</label>
                    <span class="col-xs-9"><?php echo $raw_order_info->description ?></span>
                </div>


            </fieldset>
    </div>

    <div class="inner_block">
        <div id="toolbar" class="print_hide">
            <div class="pull-left btn-toolbar">
              
            </div>
        </div>

        <div id="table_holder">
            <table id="table"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>