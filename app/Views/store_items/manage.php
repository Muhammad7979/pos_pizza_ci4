<?php echo view("partial/header"); ?>

    <script type="text/javascript">
        $(document).ready(function () {

            $("#item_type").change(function () {
                table_support.refresh();
            });

            <?php echo view('partial/bootstrap_tables_locale'); ?>

            table_support.init({
                resource: '<?php echo site_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'order_id',
                // queryParams: function () {
                //     return $.extend(arguments[0], {
                //         item_type: $("#item_type").val()
                //     });
                // },
                onLoadSuccess: function (response) {
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
            });
        });

    </script>
    <?php echo view('partial/print', array('selected_printer' => 'takings_printer')); ?>
    <div id="title_bar" class="btn-toolbar print_hide">
        <div class="inner_block">
            <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
                <span class="glyphicon glyphicon-print">&nbsp</span><?php echo lang('common_lang.common_print'); ?>
            </button>
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

    <?php
    $branch_code = $gu->getStoreBranchCode();
    $store = $gu->getStoreInfoByBranchCode($branch_code);

    $branches = $gu->getBranches();

    $address = $store['address'];
    $phone = $store['phone'];
    ?>

    <div id="company_address"><?php echo $address; ?></div>
    <div id="company_phone"><?php echo $phone; ?></div>

    <div>ITEMS LIST</div>
</div>

    <div class="inner_block">
        <div id="toolbar" class="print_hide">
            <div class="pull-left form-inline">
                <!-- <?php
                   // echo form_dropdown('item_type', $item_types, $selected_type, array('id' => 'item_type', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit'));
                ?> -->
            </div>
        </div>

        <div id="table_holder">
            <table id="table" data-sort-name="name" data-sort-order="asc"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>