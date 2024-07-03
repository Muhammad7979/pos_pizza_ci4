<?php echo view("partial/header"); ?>

    <script type="text/javascript">
        $(document).ready(function () {
            <?php echo view('partial/bootstrap_tables_locale'); ?>

            table_support.init({
                resource: '<?php echo site_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'order_id',
                queryParams: function() {
                    return $.extend(arguments[0], {
                        order_id: '<?php echo !empty($order_id) ? $order_id  : -1 ?>'
                    });
                },
            });
        });

    </script>

    <div id="title_bar" class="btn-toolbar">
        <div class="inner_block">
            <!-- <button class='btn btn-info btn-sm pull-right modal-dlg'
                    data-btn-submit='<?php //echo $this->lang->line('common_submit') ?>'
                    data-href='<?php //echo site_url($controller_name . "/view"); ?>'
                    title='<?php //echo $this->lang->line($controller_name . '_new'); ?>'>
                <span
                    class="glyphicon glyphicon-tags">&nbsp</span><?php //echo $this->lang->line($controller_name . '_new'); ?>
            </button> -->
        </div>
    </div>

    <div class="inner_block">
        <div id="toolbar">
            <div class="pull-left btn-toolbar">
                <button id="delete" class="btn btn-default btn-sm">
                    <span
                        class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
                </button>
            </div>
        </div>

        <div id="table_holder">
            <table id="table" data-sort-name="order_id" data-sort-order="desc"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>