<?php include(APPPATH . 'Views/partial/header.php'); ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?php $this->include('partial/bootstrap_tables_locale'); ?>

        table_support.init({
            resource: '<?= base_url($controller_name) ?>',
            headers: <?= $table_headers; ?>,
            pageSize: <?= $appData['lines_per_page']; ?>,
            uniqueId: 'item_kit_id'
        });

        $('#generate_barcodes').click(function() {
            window.open(
                '<?= base_url('item_kits/generate_barcodes') ?>' + '/' + table_support.selected_ids().join(':'),
                '_blank'
            );
        });

        $('#upload_item_kits').on('click',function(e){
            e.preventDefault();
             $('#upload_item_kits').prop('disabled',true);
            $.ajax({

                 url: '<?=  base_url('item_kits/upload_item_kits'); ?>',
                 type: 'GET',
                 success:function(response){
                    alert(response);
                    $('#upload_item_kits').prop('disabled', false);
                    
                 }

            })
        });
    });
</script>

<div id="title_bar" class="btn-toolbar">
    <div class="inner_block">
    <button id="upload_item_kits" class='btn btn-info btn-sm pull-right'>
                <span
                    class="glyphicon glyphicon-upload">&nbsp</span>Upload
            </button>
        <?= anchor(
            base_url($controller_name . "/view"),
            '<span class="glyphicon glyphicon-tag">&nbsp;</span>' . lang('item_kits_lang.' . $controller_name . '_new'),
            [
                'class' => 'btn btn-info btn-sm pull-right modal-dlg',
                'data-btn-new' => lang('common_lang.common_new'),
                'data-btn-submit' => lang('common_lang.common_submit'),
                'title' => lang('item_kits_lang.' . $controller_name . '_new')
            ]
        ); ?>
    </div>
</div>

<div class="inner_block">
    <div id="toolbar">
        <div class="pull-left form-inline" role="toolbar">

            <button id="delete" class="btn btn-default btn-sm print_hide" data-href="<?= base_url($controller_name . '/delete'); ?>" title="<?= lang('common_lang.common_delete'); ?>">
                <span class="glyphicon glyphicon-trash">&nbsp</span><?= lang('common_lang.common_delete'); ?>
            </button>

            <button id="generate_barcodes" class="btn btn-default btn-sm print_hide" data-href="<?= base_url($controller_name . '/generate_barcodes'); ?>" title="<?= lang('items_lang.items_generate_barcodes'); ?>">
                <span class="glyphicon glyphicon-barcode">&nbsp</span><?= lang('items_lang.items_generate_barcodes'); ?>
            </button>

        </div>
    </div>


    <div id="table_holder">
        <table id="table"></table>
    </div>
</div>

<?php include(APPPATH . 'Views/partial/footer.php'); ?>