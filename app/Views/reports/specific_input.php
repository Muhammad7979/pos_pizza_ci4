<?php  include(APPPATH . 'Views/partial/header.php'); ?>

<?php if (!empty($specific_input_data))
{
?>
<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <div id="page_title">
            <?php echo lang('reports_lang.reports_report_input'); ?>
            <a href="<?php echo base_url(). $controller_name; ?>" class="back_link" >Back to Reports</a>
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
        <?php echo form_label(lang('reports_lang.reports_date_range'), 'report_date_range_label', array('class' => 'col-xs-12')); ?>
        <div class="col-xs-3">
            <?php echo form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
        </div>
    </div>

    <div class="form-group form-group-sm" id="report_specific_input_data">
        <?php echo form_label($specific_input_name, 'specific_input_name_label', array('class' => 'col-xs-12')); ?>
        <div class="col-xs-3">
            <?php echo form_dropdown('specific_input_data', $specific_input_data, '', 'id="specific_input_data" class="form-control"'); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_sale_type'), 'reports_sale_type_label', array('class' => 'col-xs-12')); ?>
        <div id='report_sale_type' class="col-xs-3">
            <?php echo form_dropdown('sale_type', array('all' => lang('reports_lang.reports_all'),
                'sales' => lang('reports_lang.reports_sales'),
                'returns' => lang('reports_lang.reports_returns')), 'all', 'id="input_type" class="form-control"'); ?>
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

<?php  }

else{
?>
<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <div id="page_title">
            <?php echo lang('reports_lang.reports_report_input'); ?>
            <a href="<?php echo base_url(). $controller_name; ?>" class="back_link" >Back to Reports</a>
        </div>
    </div>
</div>
<div class='text-center alert alert-dismissible alert-danger'>No Record Found</div>
<?php  }?>
<?php  include(APPPATH . 'Views/partial/footer.php'); ?>


<script type="text/javascript">
    $(document).ready(function () {
        <?php  include(APPPATH . 'Views/partial/daterangepicker.php'); ?>

        $("#generate_report").click(function () {
            window.location = [window.location, start_date, end_date, $('#specific_input_data').val(), $("#sale_type").val() || 0].join("/");
        });
    });
</script>
