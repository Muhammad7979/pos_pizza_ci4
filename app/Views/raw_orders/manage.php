<?php echo view("partial/header"); ?>

    <script type="text/javascript">
        $(document).ready(function () {

            // when any filter is clicked and the dropdown window is closed
            $('#filters').on('hidden.bs.select', function (e) {
                table_support.refresh();
            });

            $('#order_time_filter').on('hidden.bs.select', function (e) {
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

            <?php echo view('partial/bootstrap_tables_locale'); ?>

            table_support.init({
                resource: '<?php echo base_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'order_id',
                queryParams: function() {
                    return $.extend(arguments[0], {
                        start_date: start_date,
                        end_date: end_date,
                        filters: $("#filters").val() || [""],
                        order_time_filter: $("#order_time_filter").val() || '',
                        order_id: '<?php echo !empty($order_id) ? $order_id  : -1 ?>'
                    });
                },
            });
        });

    </script>

    <!-- <?php //$this->load->view('partial/print', array('selected_printer' => 'takings_printer')); ?> -->
    <div id="title_bar" class="print_hide btn-toolbar">
        <div class="inner_block">
            <?php if(!$Employee->has_module_grant('raw_items_stock', session()->get('person_id'))){ ?>
                <?php if ($allow_add_new_order==1) { ?>
                <button class='btn btn-info btn-sm pull-right modal-dlg'
                        data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                        data-href='<?php echo base_url($controller_name . "/view"); ?>'
                        title='<?php echo  lang('raw_order_lang.'.$controller_name . '_new'); ?>'>
                    <span
                        class="glyphicon glyphicon-tags">&nbsp</span><?php echo lang('raw_order_lang.'.$controller_name . '_new'); ?>
                </button>
            <?php } } ?>
            <!-- <button onclick="javascript:printdoc()" class='btn btn-info btn-sm pull-right'>
                <span class="glyphicon glyphicon-print">&nbsp</span><?php //echo $this->lang->line('common_print'); ?>
            </button> -->
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

        <div>ORDERS LIST</div>
    </div>

    <div class="inner_block">
        <div id="toolbar" class="print_hide">
            <div class="pull-left form-inline" role="toolbar">
                <button id="delete" class="btn btn-default pull-left btn-sm print_hide">
                    <span class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
                </button>
            
                <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
                <?php if($displayStoresFilter==1){ ?>
                <?php echo form_multiselect('filters[]', $filters, [], array('id' => 'filters', 'class' => 'selectpicker show-menu-arrow', 'data-none-selected-text' => lang('common_lang.common_none_selected_text'), 'data-selected-text-format' => 'count > 1', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit')); ?>
                <?php } ?>
                <?php 
                    echo form_dropdown('order_time_filter', $order_time_filter, 'all', array('id' => 'order_time_filter', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit'));
                ?>
            </div>
        </div>

        <div id="table_holder">
            <table id="table" data-sort-name="order_id" data-sort-order="desc"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>