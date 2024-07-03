<?php  include(APPPATH . 'Views/partial/header.php'); ?>
<script type="text/javascript">
    $(document).ready(function () {
        <?php include(APPPATH . 'Views/partial/bootstrap_tables_locale.php'); ?>
        table_support.init({
            resource: '<?php echo base_url($controller_name)?>',
            headers: <?php echo $table_headers; ?>,
            pageSize: <?php echo $appData['lines_per_page']; ?>,
            uniqueId: 'people.person_id',
            enableActions: function () {
                var email_disabled = $("td input:checkbox:checked").parents("tr").find("td a[href^='mailto:']").length == 0;
                $("#email").prop('disabled', email_disabled);
            }
        });

        $("#email").click(function (event) {
            var recipients = $.map($("tr.selected a[href^='mailto:']"), function (element) {
                return $(element).attr('href').replace(/^mailto:/, '');
            });
            location.href = "mailto:" + recipients.join(",");
        });

    });

</script>
<style type="text/css">
    .glyphicon-fingerprint:before {
        content: url('./images/tehzeeb/fingerprint.svg');
    }
</style>
<div id="title_bar" class="btn-toolbar">
    <div class="inner_block">
        <?php
        if ($controller_name == 'customers') {
            ?>
            <button class='btn btn-info btn-sm pull-right modal-dlg'
                    data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                    data-href='<?php echo base_url($controller_name . "/excel_import"); ?>'
                    title='<?php echo lang('common_lang.customers_import_items_excel'); ?>'>
                <span
                    class="glyphicon glyphicon-import">&nbsp</span><?php echo lang('common_lang.common_import_excel'); ?>
            </button>
            <?php
        }
        ?>
        <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php echo lang('common_lang.common_submit') ?>'
                data-href='<?php echo base_url($controller_name . "/view"); ?>'
                title='<?php echo lang($controller_name.'_lang.'. $controller_name .'_new'); ?>'>
            <span
                class="glyphicon glyphicon-user">&nbsp</span><?php echo lang($controller_name.'_lang.'. $controller_name .'_new'); ?>
        </button>
        <!-- <?php //if ($controller_name==='employees') { ?>
        <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php // echo lang('common_lang.common_submit') ?>'
                data-href='<?php // echo base_url($controller_name . "/view"); ?>'
                title='<?php // echo lang('employees_lang.employees_new'); ?>'>
            <span
                class="glyphicon glyphicon-user">&nbsp</span><?php // echo lang('employees_lang.employees_new'); ?>
        </button>
        <?php // }elseif($controller_name==='suppliers'){?>
       
        <button class='btn btn-info btn-sm pull-right modal-dlg'
                data-btn-submit='<?php // echo lang('common_lang.common_submit') ?>'
                data-href='<?php // echo base_url($controller_name . "/view"); ?>'
                title='<?php // echo lang('suppliers_lang.suppliers_new'); ?>'>
            <span
                class="glyphicon glyphicon-user">&nbsp</span><?php // echo lang('suppliers_lang._lang.suppliers_new'); ?>
        </button>
        <?php // }?> -->
    </div>
</div>

<div class="inner_block">

    <div id="toolbar">
        <div class="pull-left btn-toolbar">
            <button id="delete" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-trash">&nbsp</span><?php echo lang("common_lang.common_delete"); ?>
            </button>
            <button id="email" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-envelope">&nbsp</span><?php echo lang("common_lang.common_email"); ?>
            </button>
        </div>
    </div>

    <div id="table_holder">
        <table id="table"></table>
        
    </div>
</div>


<script type="text/javascript" src="<?= base_url('dist/cloudabis/CloudABIS-Helper.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('dist/cloudabis/CloudABIS-ScanR.js') ?>"></script>

<script type="text/javascript">
    setConfiguration();
</script>
<?php include(APPPATH . 'Views/partial/footer.php'); ?>
