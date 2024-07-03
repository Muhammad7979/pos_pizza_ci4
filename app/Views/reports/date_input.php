<?php  include(APPPATH . 'Views/partial/header.php'); ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <div id="page_title">
            <?php
            $report_type = service('uri')->getSegment(2);
            $report_type = str_replace('_',' ',$report_type);
            $report_type = str_replace('-',' ',$report_type);
            $report_type = ucwords($report_type);
            echo "<b>".$report_type ." </b> Report"; ?>
<a href="<?= base_url('/reports') ?>" class="back_link">Back to Reports</a> 
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

    <div class="form-group form-group-sm">
        <?php
        if ($mode == 'sale') {
            ?>
            <?php echo form_label(lang('reports_lang.reports_sale_type'), 'reports_sale_type_label', array('class' => 'col-xs-12')); ?>
            <div id='report_sale_type' class="col-xs-3">
                <?php echo form_dropdown('sale_type', $gu->getSaleTypesForFilter(), 'all', array('id' => 'input_type', 'class' => 'form-control')); ?>
            </div>
            <?php
        } elseif ($mode == 'receiving') {
            ?>
            <?php echo form_label(lang('reports_lang.reports_receiving_type'), 'reports_receiving_type_label', array('class' => 'col-xs-12')); ?>
            <div id='report_receiving_type' class="col-xs-3">
                <?php echo form_dropdown('receiving_type', array('all' => lang('reports_lang.reports_all'),
                    'receiving' => lang('reports_lang.reports_receivings'),
                    'returns' => lang('reports_lang.reports_returns'),
                    'requisitions' => lang('reports_lang.reports_requisitions')), 'all', array('id' => 'input_type', 'class' => 'form-control')); ?>
            </div>
            <?php
        }
        ?>
    </div>

    <?php
    if (!empty($stock_locations) && count($stock_locations) > 1) {
        ?>
        <div class="form-group form-group-sm" style="display: none;">
            <?php echo form_label(lang('reports_lang.reports_stock_location'), 'reports_stock_location_label', array('class' => 'col-xs-12')); ?>
            <div id='report_stock_location' class="col-xs-3">
                <?php echo form_dropdown('stock_location', $stock_locations, 'all', array('id' => 'location_id', 'class' => 'form-control')); ?>
            </div>
        </div>
        <?php
    }
    ?>

    <div class="form-group form-group-sm">
        <?php echo form_label('Payment Type', 'report_payment_label', array('class' => 'col-xs-12')); ?>
        <div class="col-xs-3">
            <?php echo form_dropdown('payment_type', [
                    'all' => "All Payment Types",
                    'cash' => "Cash",
                    'credit' => "Credit"
            ], 'all', array('id' => 'payment_type', 'class' => 'form-control')); ?>

        </div>
    </div>

    <?php if($gu->isServer()) { ?>
        <div class="form-group form-group-sm">
            <?php echo form_label('Branch', 'branch_code', array('class' => 'col-xs-12')); ?>
            <div class="col-xs-3">
                <?php echo form_dropdown('branch_code', $gu->getBranches(),
                    'all', array('id' => 'branch_code', 'class' => 'form-control')); ?>

            </div>
        </div>
        <?php
    }
    else{
        ?>
        <input type="hidden" id="branch_code" name="branch_code" value="<?php echo $gu->getStoreBranchCode(); ?>"/>
    <?php
    }
    ?>

    <?php if($gu->isServer() && strpos(service('uri')->getSegment(2), 'sales_for_directors')) { ?>
        <div class="form-group form-group-sm">
            <?php echo form_label('Item Type', 'item_type', array('class' => 'col-xs-12')); ?>
            <div class="col-xs-3">
                <?php echo form_dropdown('item_type', [
                    'all' => "All Items",
                    'specific' => "Specific Items"],
                    'all', array('id' => 'item_type', 'class' => 'form-control')); ?>

            </div>
        </div>

        <div class="form-group form-group-sm" id="item_section" style="display: none;">
            <?php echo form_label(lang('reports_lang.reports_item'), 'item', array('class'=>'col-xs-12')); ?>
            <div class='col-xs-3'>
                <?php echo form_input(array(
                        'name'=>'item',
                        'id'=>'item',
                        'class'=>'form-control input-sm')
                        );?>
            </div>
        </div>

        <input type="hidden" id="item_id" name="item_id" value=""/>

    <?php
        }else{
    ?>
        <input type="hidden" id="item_type" name="item_type" value="all"/>
    <?php
    }
    ?>

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

        <?php 

            if(strpos(service('uri')->getSegment(2), 'sales_for_directors')) {
                include(APPPATH . 'Views/partial/daterangetimepicker.php');
                // $this->load->view('partial/daterangetimepicker');
            }else{
                include(APPPATH . 'Views/partial/daterangepicker.php');
                // $this->load->view('partial/daterangepicker');
            }
        ?>

        $("#item").autocomplete({
            source: '<?php echo base_url("items/suggest"); ?>',
            minChars:0,
            autoFocus: false,
            delay:10,
            appendTo: ".modal-content",
            select: function(e, ui) {
                $("#item").val(ui.item.label);
                $("#item_id").val(ui.item.value);
                return false;
            }
        });

        $("#generate_report").click(function () {

            var startD = new Date(start_date).toISOString();
            var endD = new Date(end_date).toISOString();

            window.location = [window.location, startD, endD,
                $("#input_type").val() || 0, $("#location_id").val() || 'all',
                $("#payment_type").val(), $("#branch_code").val(), 
                $("#item_type").val() || 'all', $("#item_id").val()].join("/");
        });

        $('#item_type').change(function () {
            $('#item_section').hide();
            $("#item").val('');
            $("#item_id").val('');
            if($(this).val() != 'all'){
                $('#item_section').show();
            }
        });
    });
</script>