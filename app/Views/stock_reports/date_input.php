<?php  echo view("partial/header"); ?>
<div id="title_bar" class="btn-toolbar print_hide">
    <div class="inner_block">
        <div id="page_title">
            <?php
            $report_type = service('uri')->getSegment(2);
            $report_type = str_replace('i_','_',$report_type);
            $report_type = str_replace('_',' ',$report_type);
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

    <?php
        if (!empty($stock_warehouses) && count($stock_warehouses) > 0) {
    ?>
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('reports_lang.reports_warehouse'), 'report_warehouse_label', array('class' => 'col-xs-12')); ?>
            <div id='report_warehouse' class="col-xs-3">
                <?php echo form_dropdown('stock_warehouses', $stock_warehouses, 'all', array('id' => 'person_id', 'class' => 'form-control')); ?>
            </div>
        </div>
    <?php
        }
    ?>

    <?php
        if (!empty($stock_stores) && count($stock_stores) > 0) {
    ?>
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('reports_lang.reports_store'), 'report_store_label', array('class' => 'col-xs-12')); ?>
            <div id='report_store' class="col-xs-3">
                <?php echo form_dropdown('stock_stores', $stock_stores, 'all', array('id' => 'store_id', 'class' => 'form-control')); ?>
            </div>
        </div>
    <?php
        }
    ?>
    
    <?php
        if (!empty($item_types) && count($item_types) > 0) {
    ?>
    <div class="form-group form-group-sm">
        <?php echo form_label(lang('stock_reports_lang.reports_store_items_type'), 'item_types_label', array('class' => 'col-xs-12')); ?>
        <div id='item_types' class="col-xs-3">
            <?php echo form_dropdown('item_type', $item_types, 'all', array('id' => 'item_type', 'class' => 'form-control')); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_items_from'), 'item_from_label', array('id' => 'item_from_name', 'class' => 'col-xs-12')); ?>
        <div id='item_types' class="col-xs-3">
            <?php echo form_dropdown('item_from', $warehouses, 'all', array('id' => 'item_from', 'class' => 'form-control')); ?>
        </div>
    </div>
    <?php
        }
    ?>

    <?php
        if (!empty($item_type) && count($item_type) > 0) {
    ?>
    <div class="form-group form-group-sm">
        <?php echo form_label(lang('stock_reports_lang.reports_store_items_type'), 'item_types_label', array('class' => 'col-xs-12')); ?>
        <div id='item_types' class="col-xs-3">
            <?php echo form_dropdown('item_type', $item_type, 'all', array('id' => 'item_type', 'class' => 'form-control')); ?>
        </div>
    </div>
    <?php
        }
    ?>

    <?php
        if (!empty($order_types) && count($order_types) > 0) {
    ?>
    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_order_type'), 'item_types_label', array('class' => 'col-xs-12')); ?>
        <div id='item_types' class="col-xs-3">
            <?php echo form_dropdown('item_type', $order_types, 'all', array('id' => 'item_type', 'class' => 'form-control')); ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_order_from'), 'order_from_label', array('id' => 'order_from_name', 'class' => 'col-xs-12')); ?>
        <div id='item_types' class="col-xs-3">
            <?php echo form_dropdown('item_from', $warehouses, 'all', array('id' => 'item_from', 'class' => 'form-control')); ?>
        </div>
    </div>
    <div class="form-group form-group-sm">
        <?php echo form_label(lang('reports_lang.reports_order_time'), 'item_time_label', array('class' => 'col-xs-12')); ?>
        <div class="col-xs-3">
            <?php echo form_dropdown('order_time', $order_time, 'all', array('id' => 'order_time', 'class' => 'form-control')); ?>
        </div>
    </div>
    <?php
        }
    ?>

    <?php
        if (!empty($stock_counters) && count($stock_counters) > 0) {
    ?>
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('reports_lang.reports_counter'), 'report_counter_label', array('class' => 'col-xs-12')); ?>
            <div id='report_counter' class="col-xs-3">
                <?php echo form_dropdown('stock_counters', $stock_counters, 'all', array('id' => 'counter_id', 'class' => 'form-control')); ?>
            </div>
        </div>
    <?php
        }
    ?>

    <?php
        if (!empty($stock_vendors) && count($stock_vendors) > 0) {
    ?>
        <div class="form-group form-group-sm">
            <?php echo form_label(lang('reports_lang.reports_vendor'), 'report_vendor_label', array('class' => 'col-xs-12')); ?>
            <div id='report_vendor' class="col-xs-3">
                <?php echo form_dropdown('stock_vendors', $stock_vendors, 'all', array('id' => 'vendor_id', 'class' => 'form-control')); ?>
            </div>
        </div>
    <?php
        }
    ?>
    <?php
     if($gu->isServer()) { ?>
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
        <input type="hidden" id="branch_code" value="<?php echo $gu->getStoreBranchCode(); ?>"/>
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


<?php echo view("partial/footer"); ?>

<script type="text/javascript">
    $(document).ready(function () {
        <?php echo view('partial/daterangepicker'); ?>

        $("#generate_report").click(function () {
            <?php if (isset($AjaxTrue) && $AjaxTrue) { ?>
                 window.location = [window.location, start_date, end_date, $("#store_id").val(), $("#counter_id").val() || $("#counter_id").val()].join("/"); 
            <?php }else if (isset($AjaxTrue2) && $AjaxTrue2) { ?>
                window.location = [window.location, start_date, end_date, $("#store_id").val(), $("#counter_id").val() || $("#vendor_id").val()].join("/"); 
            <?php }else{ ?>
                 window.location = [window.location, start_date, end_date, $("#person_id").val() || $("#store_id").val() || $("#counter_id").val() || $("#vendor_id").val() || 0, $("#item_type").val() || 0, $("#item_from").val() || 'all', $("#order_time").val() || 'all',$("#branch_code").val()].join("/");
            <?php } ?>
        });
        //AjaxTrue = true for admin only so he can search for all counter for stores using ajax
        <?php if (isset($AjaxTrue) && $AjaxTrue) { ?>
            $('#store_id').change(function(e){
                e.preventDefault();
                var val = $(this).val();
                var select = $('#counter_id');
                select.empty();
                select.append('<option value="all"><?php echo lang('reports_lang.reports_all') ?></option>');
                $.ajax({
                    type:'GET',
                    dataType: 'JSON',
                    url:"<?php echo base_url('reports/getCountersAjax/'); ?>"+val,
                    success:function(data){
                        console.log(data);
                        $.each(data, function(key,value){
                            select.append('<option value="'+ key +'">'+ value +'</option>');
                        });
                    }
                });
            });
        <?php } ?>
        //AjaxTrue2 = true for admin only so he can search for all counter for stores using ajax
        <?php if (isset($AjaxTrue2) && $AjaxTrue2) { ?>
            $('#store_id').change(function(e){
                e.preventDefault();
                var val = $(this).val();
                var select = $('#vendor_id');
                select.empty();
                select.append('<option value="all"><?php echo lang('reports_lang.reports_all') ?></option>');
                $.ajax({
                    type:'GET',
                    dataType: 'JSON',
                    url:"<?php echo base_url('reports/getVendorsAjax/'); ?>"+val,
                    success:function(data){
                        console.log(data);
                        $.each(data, function(key,value){
                            select.append('<option value="'+ key +'">'+ value +'</option>');
                        });
                    }
                });
            });
        <?php } ?>

            $('#store_id').change(function(e){
                e.preventDefault();
                var id = $(this).val();
                var val = $("#item_type").val();
                var select = $('#item_from');
                select.empty();
                select.append('<option value="all"><?php echo lang('reports_lang.reports_all') ?></option>');
                if(val!='all'){
                    $.ajax({
                        type:'GET',
                        dataType: 'JSON',
                        url:"<?php echo base_url('reports/getFromAjax/'); ?>"+val+"/"+id,
                        success:function(data){
                            console.log(data);
                            $.each(data, function(key,value){
                                select.append('<option value="'+ key +'">'+ value +'</option>');
                            });
                        }
                    });
                }
            });

            $('#item_type').change(function(e){
                e.preventDefault();
                var val = $(this).val();
                var id = $("#store_id").val() || 0;
                var select = $('#item_from');
                select.empty();
                select.append('<option value="all"><?php echo lang('reports_lang.reports_all') ?></option>');
                if(val!='all'){
                    $.ajax({
                        type:'GET',
                        dataType: 'JSON',
                        url:"<?php echo base_url('reports/getFromAjax/'); ?>"+val+"/"+id,
                        success:function(data){
                            console.log(data);
                            $.each(data, function(key,value){
                                select.append('<option value="'+ key +'">'+ value +'</option>');
                            });
                        }
                    });
                }
            });


    });
</script>