<?php echo view("partial/header"); ?>

    <script type="text/javascript">
        $(document).ready(function () {
            <?php echo view('partial/bootstrap_tables_locale'); ?>

            table_support.init({
                resource: '<?php echo base_url('notifications');?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'id',
                queryParams: function () {
                    return $.extend(arguments[0], {
                        order: 'desc',
                        sort: 'created_at',
                    });
                },
            });
        });

    </script>

    <div id="title_bar" class="btn-toolbar">
        <div class="inner_block">
            
        </div>
    </div>

    <div class="inner_block">
        <div id="toolbar">
            <div class="pull-left btn-toolbar">
                <button id="delete" class="btn btn-default btn-sm">
                    <span
                        class="glyphicon glyphicon-trash">&nbsp</span><?php echo  lang("common_lang.common_delete"); ?>
                </button>
            </div>
        </div>

        <div id="table_holder">
            <table id="table"></table>
        </div>
    </div>
<?php echo view("partial/footer"); ?>