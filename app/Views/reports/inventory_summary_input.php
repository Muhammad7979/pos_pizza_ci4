<?php  include(APPPATH . 'Views/partial/header.php'); ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <div id="page_title">
            <?php echo lang('reports_lang.reports_report_input'); ?>
            <a href="<?php echo $controller_name; ?>" class="back_link" >Back to Reports</a>
        </div>
    </div>
</div>

<div class="inner_block" style="margin-top: 30px;">

    <?php
    if (isset($error)) {
        echo "<div class='alert alert-dismissible alert-danger'>" . $error . "</div>";
    }
    ?>

    <?php echo form_open('#', array('id' => 'item_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>

    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_stock_location'), 'reports_stock_location_label', array('class' => 'col-xs-12')); ?>
        <div id='report_stock_location' class="col-xs-3">
            <?php echo form_dropdown('stock_location', $stock_locations, 'all', 'id="location_id" class="form-control"'); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_item_count'), 'reports_item_count_label', array('class' => 'col-xs-12')); ?>
        <div id='report_item_count' class="col-xs-3">
            <?php echo form_dropdown('item_count', $item_count, 'all', 'id="item_count" class="form-control"'); ?>
        </div>
    </div>

    <?php
    echo form_button(array(
            'name' => 'generate_report',
            'id' => 'generate_report',
            'content' => lang('common_lang.common_submit'),
            'class' => 'btn btn-primary btn-sm')
    );
    ?>
    <?php echo form_close(); ?>
</div>
<?php  include(APPPATH . 'Views/partial/footer.php'); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#generate_report").click(function () {
            window.location = [window.location, $("#location_id").val(), $("#item_count").val()].join("/");
        });
    });
</script>