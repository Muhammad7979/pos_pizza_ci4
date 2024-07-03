<?php echo view("partial/header"); ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#generate_barcodes').click(function () {
                window.open(
                    'index.php/categories/generate_barcodes/' + table_support.selected_ids().join(':'),
                    '_blank' // <- This is what makes it open in a new window.
                );
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

            <?php echo view('partial/bootstrap_tables_locale'); ?>

            table_support.init({
                resource: '<?php echo base_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'item_id',
                queryParams: function () {
                    return $.extend(arguments[0], {
                        start_date: start_date,
                        end_date: end_date
                    });
                },
                onLoadSuccess: function (response) {
                    $('a.rollover').imgPreview({
                        imgCSS: {width: 200},
                        distanceFromCursor: {top: 10, left: -210}
                    });
                },
            });

            $('#btn_sync_categories').click(function(e){
                e.preventDefault();

                var btn = $("#btn_sync_categories");

                var oldText = btn.html();

                btn.attr("disabled", true);

                btn.text("Updating...");

                $.ajax({
                    url: $(this).data('href'),
                    success: function (data) {
                        //console.log(data);
                        btn.html(oldText);
                        alert(data);
                        window.location.href = "<?php echo base_url('categories'); ?>";
                    },
                    error: function (data) {
                        alert('something went wrong');
                    }
                });

            })
        });
    </script>

    <div id="title_bar" class="btn-toolbar">
        <div class="inner_block">
           
           <button class='btn btn-info btn-sm pull-right modal-dlg'
                    data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                    data-href='<?php echo base_url($controller_name . "/view"); ?>'
                    title='<?php echo lang($controller_name.'_lang.'.$controller_name . '_new'); ?>'>
                <span
                    class="glyphicon glyphicon-tag">&nbsp</span><?php echo lang($controller_name.'_lang.'.$controller_name . '_new'); ?>
            </button>

            <button class='btn btn-info btn-sm pull-left'
                    data-href='<?php echo base_url('categories/sync_categories'); ?>'
                    title='Update Items Record' id="btn_sync_categories" >
                <span
                    class="glyphicon glyphicon-cloud-download">&nbsp</span>Update Items
            </button>

        </div>
    </div>

    <div class="inner_block">
        <div id="toolbar">
            <div class="pull-left form-inline">
                <button id="delete" class="btn btn-default btn-sm">
                    <span
                        class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
                </button>
                <button id="generate_barcodes" class="btn btn-default btn-sm print_hide"
                    data-href='<?php echo base_url($controller_name . "/generate_barcodes"); ?>'
                    title='<?php echo lang('raw_items_lang.raw_items_generate_barcodes'); ?>'>
                    <span
                        class="glyphicon glyphicon-barcode">&nbsp</span><?php echo lang("raw_items_lang.raw_items_generate_barcodes"); ?>
                </button>

                <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
            </div>
        </div>

        <div id="table_holder">
            <table id="table"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>