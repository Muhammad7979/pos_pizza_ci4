<?php include(APPPATH . 'Views/partial/header.php'); ?>
    <script type="text/javascript">
        $(document).ready(function () {
           
            table_support.init({
                resource: '<?php echo site_url($controller_name);?>',
                headers: <?php echo $table_headers; ?>,
                pageSize: <?php echo $appData['lines_per_page']; ?>,
                uniqueId: 'giftcard_id'
            });

            $('#btn_sync_giftcard').click(function(e){
                e.preventDefault();

                var btn = $("#btn_sync_giftcard");

                var oldText = btn.html();

                btn.attr("disabled", true);

                btn.text("Updating...");

                $.ajax({
                    url: $(this).data('href'),
                    success: function (data) {
                        //console.log(data);
                        btn.html(oldText);
                        alert(data);
                        window.location.href = "<?php echo base_url('giftcards'); ?>";
                    },
                    error: function (data) {
                        alert('something went wrong');
                    }
                });

            });

            
        });
    </script>

    <div id="title_bar" class="btn-toolbar">
        <div class="inner_block">

            <!-- <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php // echo lang('common_lang.common_submit') ?>'
                data-href='<?php // echo site_url($controller_name . "/excel_import"); ?>'
                title='<?php // echo lang('customers_lang.customers_import_items_excel'); ?>'>
                <span class="glyphicon glyphicon-import">&nbsp</span><?php // echo lang('common_lang.common_import_excel'); ?>
            </button> -->
        
            <button class='btn btn-info btn-sm pull-right modal-dlg'
                    data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                    data-href='<?php echo site_url($controller_name . "/view"); ?>'
                    title='<?php echo lang('giftcards_lang.giftcards_new'); ?>'>
                <span
                    class="glyphicon glyphicon-heart">&nbsp</span><?php echo lang('giftcards_lang.giftcards_new'); ?>
            </button>

            <button class='btn btn-info btn-sm pull-left'
                    data-href='<?php echo base_url('giftcards/sync_giftcards'); ?>'
                    title='Update Giftcards Record' id="btn_sync_giftcard" >
                <span
                    class="glyphicon glyphicon-cloud-download">&nbsp</span>Update Giftcards
            </button>

        </div>
    </div>

    <div class="inner_block">
        <div id="toolbar">
            <div class="pull-left btn-toolbar">
                <button id="delete" class="btn btn-default btn-sm"
                data-href='<?php echo base_url('giftcards/sync_giftcards'); ?>'
                >
                    <span
                        class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
                </button>
            </div>
        </div>

        <div id="table_holder">
            <table id="table" data-detail-formatter="detailFormatter" ></table>
        </div>
    </div>
    <?php include(APPPATH . 'Views/partial/footer.php'); ?>