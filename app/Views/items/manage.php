<?= $this->include("partial/header") ?>

<?php use App\Models\Employee; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-dismissible alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?= $error ?>
    </div>
<?php endif; ?>

<?php if (!empty($warning)): ?>
    <div class="alert alert-dismissible alert-warning">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?= $warning ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-dismissible alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <?= $success ?>
    </div>
<?php endif; ?>

<div class="alert alert-dismissible alert-success" style="display: none;">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <b id="notifications"></b>
</div>


<script>
    $(document).ready(function () {
        $('#generate_barcodes').click(function () {
            window.open(
                '<?= base_url("items/generate_barcodes") ?>' + '/' + table_support.selected_ids().join(':'),
                '_blank' // This is what makes it open in a new window.
            );
        });

        // when any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function (e) {
            table_support.refresh();
        });

        // load the preset datarange picker
        <?= view('partial/daterangepicker.php'); ?>
        // set the beginning of time as the starting date
        $('#daterangepicker').data('daterangepicker').setStartDate("<?= date($appData['dateformat'], mktime(0, 0, 0, 01, 01, 2010)) ?>");
        // update the hidden inputs with the selected dates before submitting the search data
        var start_date = "<?= date('Y-m-d', mktime(0, 0, 0, 01, 01, 2010)) ?>";
        $("#daterangepicker").on('apply.daterangepicker', function (ev, picker) {
            table_support.refresh();
        });

        $("#stock_location").change(function () {
            table_support.refresh();
        });

        <?php $this->include('partial/bootstrap_tables_locale'); ?>

        table_support.init({
            employee_id: <?= (new Employee())->get_logged_in_employee_info()->person_id ?>,
            resource: '<?= base_url($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $appData['lines_per_page']; ?>,
            uniqueId: 'items.item_id',
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
                    imgCSS: { width: 200 },
                    distanceFromCursor: { top: 10, left: -210 }
                });
            }
        });

        $('#btn_sync').click(function (e) {
            e.preventDefault();
            var btn = $("#btn_sync");

            var oldText = btn.html();

            btn.attr("disabled", true);

            btn.text("Updating...");

            $.ajax({
                url: $(this).data('href'),
                success: function (data) {
                    // console.log(data);
                    btn.html(oldText);
                    alert(data);
                    window.location.href = "<?= base_url('items') ?>";
                },
                error: function (data) {
                    alert('something went wrong');
                }
            });

        });
    });
</script>



<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">

        <?= anchor(
            base_url($controller_name . "/excel_import"),
            '<span class="glyphicon glyphicon-import">&nbsp;</span>' . lang('common_lang.common_import_excel'),
            [
                'class' => 'btn btn-info btn-sm pull-right modal-dlg',
                'data-btn-submit' => lang('common_lang.common_submit'),
                'title' => lang('customers_lang.customers_import_items_excel')
            ]
        ); ?>

            <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                data-href='<?php echo base_url($controller_name . "/excel_import_update_prices"); ?>' title='Update Prices CSV'>
            <span class="glyphicon glyphicon-import">&nbsp</span>Update Prices CSV
        </button>

        <?= anchor(
            base_url($controller_name . "/view"),
            '<span class="glyphicon glyphicon-tag">&nbsp;</span>' . lang('items_lang.' . $controller_name . '_new'),
            [
                'class' => 'btn btn-info btn-sm pull-right modal-dlg',
                'data-btn-new' => lang('common_lang.common_new'),
                'data-btn-submit' => lang('common_lang.common_submit'),
                'title' => lang('items_lang.' . $controller_name . '_new')
            ]
        );

        if (!empty($appData['branch_specific_price'])) :
            echo anchor(
                base_url($controller_name . "/sync/?branch=") . $this->gu->getStoreBranchCode(),
                '<span class="glyphicon glyphicon-cloud-download">&nbsp;</span>' . 'Update Items',
                [
                    'class' => 'btn btn-info btn-sm pull-left',
                    'id'    => 'btn_sync',
                    'title' => 'Update Items Record'
                ]
            );
        else : ?>
            
           
            <button class='btn btn-info btn-sm pull-left'
                    data-href='<?php echo base_url('items/sync'); ?>'
                    title='Update Items Record' id="btn_sync" >
                <span
                    class="glyphicon glyphicon-cloud-download">&nbsp</span>Update items
            </button>
            
        <?php  endif; ?>
    </div>
</div>

<div class="inner_block">
    <div id="toolbar">
        <div class="pull-left form-inline" role="toolbar">
            <button id="delete" class="btn btn-default btn-sm print_hide" data-href="<?= base_url($controller_name . '/delete'); ?>" title="<?= lang('common_lang.common_delete'); ?>">
                <span class="glyphicon glyphicon-trash">&nbsp</span><?= lang('common_lang.common_delete'); ?>
            </button>
            <button id="bulk_edit" class="btn btn-default btn-sm modal-dlg print_hide" data-btn-submit="<?= lang('common_lang.common_submit'); ?>" data-href="<?= base_url($controller_name . '/bulk_edit'); ?>" title="<?= lang('items_lang.items_edit_multiple_items'); ?>">
                <span class="glyphicon glyphicon-edit">&nbsp</span><?= lang('items_lang.items_bulk_edit'); ?>
            </button>
            <button id="generate_barcodes" class="btn btn-default btn-sm print_hide" data-href="<?= base_url($controller_name . '/generate_barcodes'); ?>" title="<?= lang('items_lang.items_generate_barcodes'); ?>">
                <span class="glyphicon glyphicon-barcode">&nbsp</span><?= lang('items_lang.items_generate_barcodes'); ?>
            </button>
            <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']); ?>
            <?= form_multiselect('filters[]', $filters, [], ['id' => 'filters', 'class' => 'selectpicker show-menu-arrow', 'data-none-selected-text' => lang('common_lang.common_none_selected_text'), 'data-selected-text-format' => 'count > 1', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']); ?>
            <?php
            if (count($stock_locations) > 1) {
                echo form_dropdown('stock_location', $stock_locations, $stock_location, ['id' => 'stock_location', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']);
            }
            ?>
        </div>
    </div>


    <div id="table_holder">
        <table id="table"></table>
    </div>
</div>

<?php include(APPPATH . 'Views/partial/footer.php'); ?>
