<?php echo view("partial/header"); ?>


    <?php
    if (isset($error)) {
        echo "<div class='alert alert-dismissible alert-danger'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $error . "</div>";
    }

    if (!empty($warning)) {
        echo "<div class='alert alert-dismissible alert-warning'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $warning . "</div>";
    }

    if (isset($success)) {
        echo "<div class='alert alert-dismissible alert-success'><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" . $success . "</div>";
    }
    ?>

    <div class='alert alert-dismissible alert-success' style="display: none;">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <b id="notifications"></b>
    </div>



<script type="text/javascript">
    $(document).ready(function () {
        $('#generate_barcodes').click(function () {
            window.open(
                'index.php/raw_items/generate_barcodes/' + table_support.selected_ids().join(':'),
                '_blank' // <- This is what makes it open in a new window.
            );
        });

        // when any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function (e) {
            table_support.refresh();
        });

        // load the preset datarange picker
        <?php echo view('partial/daterangepicker'); ?>
        // set the beginning of time as starting date
        $('#daterangepicker').data('daterangepicker').setStartDate("<?php echo date($appData['dateformat'], mktime(0, 0, 0, 01, 01, 2010));?>");
        // update the hidden inputs with the selected dates before submitting the search data
        var start_date = "<?php echo date('Y-m-d', mktime(0, 0, 0, 01, 01, 2010));?>";
        $("#daterangepicker").on('apply.daterangepicker', function (ev, picker) {
            table_support.refresh();
        });

        $("#stock_location").change(function () {
            table_support.refresh();
        });

        <?php echo view('partial/bootstrap_tables_locale'); ?>

        table_support.init({
            employee_id: <?php echo $user_info->person_id; ?>,
            resource: '<?php echo base_url($controller_name);?>',
            headers: <?php echo $table_headers; ?>,
            pageSize: <?php echo $appData['lines_per_page']; ?>,
            uniqueId: 'raw_items.item_id',
            queryParams: function () {
                return $.extend(arguments[0], {
                    start_date: start_date,
                    end_date: end_date,
                    stock_location: $("#stock_location").val(),
                    filters: $("#filters").val() || [""]
                });
            },
            onLoadSuccess: function (response) {
                $('a.rollover').imgPreview({
                    imgCSS: {width: 200},
                    distanceFromCursor: {top: 10, left: -210}
                });
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

        // $('#btn_sync').click(function(e){
        //     e.preventDefault();

        //     var btn = $("#btn_sync");

        //     var oldText = btn.html();

        //     btn.attr("disabled", true);

        //     btn.text("Updating...");

        //     $.ajax({
        //         url: $(this).data('href'),
        //         success: function (data) {
        //             //console.log(data);
        //             btn.html(oldText);
        //             alert(data);
        //             window.location.href = "<?php //echo base_url('index.php/raw_items'); ?>";
        //         },
        //         error: function (data) {
        //             alert('something went wrong');
        //         }
        //     });

        // });
    });
</script>

<?php echo view('partial/print', array('selected_printer' => 'takings_printer')); ?>
<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                data-href='<?php echo base_url($controller_name . "/excel_import"); ?>'
                title='<?php echo lang('customers_lang.customers_import_raw_items_excel'); ?>'>
            <span class="glyphicon glyphicon-import">&nbsp</span><?php echo lang('common_lang.common_import_excel'); ?>
        </button>

        <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-new='<?php echo lang('common_lang.common_new') ?>'
                data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                data-href='<?php echo base_url($controller_name . "/view"); ?>'
                title='<?php echo lang($controller_name.'_lang.'.$controller_name . '_new'); ?>'>
            <span
                class="glyphicon glyphicon-tag">&nbsp</span><?php echo lang($controller_name.'_lang.'.$controller_name . '_new'); ?>
        </button>
        
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

    <div>RAW ITEMS LIST</div>
</div>

<div class="inner_block">
    <div id="toolbar" class="print_hide">
        <div class="pull-left form-inline" role="toolbar">
            <button id="delete" class="btn btn-default btn-sm print_hide">
                <span class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
            </button>
            <button id="bulk_edit" class="btn btn-default btn-sm modal-dlg print_hide" ,
                    data-btn-submit='<?php echo lang('common_lang.common_submit') ?>' ,
                    data-href='<?php echo base_url($controller_name . "/bulk_edit"); ?>'
                    title='<?php echo lang('raw_items_lang.raw_items_edit_multiple_items'); ?>'>
                <span class="glyphicon glyphicon-edit">&nbsp</span><?php echo lang("raw_items_lang.raw_items_bulk_edit"); ?>
            </button>
            <button id="generate_barcodes" class="btn btn-default btn-sm print_hide"
                    data-href='<?php echo base_url($controller_name . "/generate_barcodes"); ?>'
                    title='<?php echo lang('raw_items.raw_items_generate_barcodes'); ?>'>
                <span
                    class="glyphicon glyphicon-barcode">&nbsp</span><?php echo lang("raw_items_lang.raw_items_generate_barcodes"); ?>
            </button>

            <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
            <?php echo form_multiselect('filters[]', $filters, [], array('id' => 'filters', 'class' => 'selectpicker show-menu-arrow', 'data-none-selected-text' => lang('common_lang.common_none_selected_text'), 'data-selected-text-format' => 'count > 1', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit')); ?>
            <?php
            if (count($stock_locations) > 1) {
                echo form_dropdown('stock_location', $stock_locations, $stock_location, array('id' => 'stock_location', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit'));
            }
            ?>
        </div>
    </div>

    <div id="table_holder">
        <table id="table"></table>
    </div>
</div>


<?php echo view("partial/footer"); ?>
