<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('raw_items_processing/save_inventory/'.$item_info->item_id, array('id'=>'item_form', 'class'=>'form-horizontal')); ?>
	<ul class="nav nav-tabs nav-justified" data-tabs="tabs">
        <li class="active" role="presentation">
            <a data-toggle="tab"
               href="#item_basic_info"><?php echo $this->lang->line("raw_items_processing_item_information"); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#item_material_info"><?php echo $this->lang->line("raw_items_processing_item_material_information"); ?></a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade in active" id="item_basic_info">
			<fieldset id="inv_item_basic_info">
				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_item_number'), 'name', array('class'=>'control-label col-xs-3')); ?>
					<div class="col-xs-8">
						<div class="input-group">
							<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
							<?php echo form_input(array(
									'name'=>'item_number',
									'id'=>'item_number',
									'class'=>'form-control input-sm',
									'disabled'=>'',
									'value'=>$item_info->item_number)
									);?>
						</div>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_name'), 'name', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<?php echo form_input(array(
								'name'=>'name',
								'id'=>'name',
								'class'=>'form-control input-sm',
								'disabled'=>'',
								'value'=>$item_info->name)
								); ?>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_category'), 'category', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<div class="input-group">
							<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
							<?php echo form_input(array(
									'name'=>'category',
									'id'=>'category',
									'class'=>'form-control input-sm',
									'disabled'=>'',
									'value'=>$item_info->category)
									);?>
						</div>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_stock_location'), 'stock_location', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<?php echo form_dropdown('stock_location', $stock_locations, current($stock_locations), array('onchange'=>'fill_quantity(this.value)', 'class'=>'form-control'));	?>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_current_quantity'), 'quantity', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-4'>
						<?php echo form_input(array(
								'name'=>'quantity',
								'id'=>'quantity',
								'class'=>'form-control input-sm',
								'disabled'=>'',
								'value'=>to_quantity_decimals(current($item_quantities)))
								); ?>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_add_minus'), 'quantity', array('class'=>'required control-label col-xs-3')); ?>
					<div class='col-xs-4'>
						<?php echo form_input(array(
								'name'=>'newquantity',
								'id'=>'newquantity',
								'class'=>'form-control input-sm')
								); ?>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('items_inventory_comments'), 'description', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<?php echo form_textarea(array(
								'name'=>'trans_comment',
								'id'=>'trans_comment',
								'class'=>'form-control input-sm')
								);?>
					</div>
				</div>
			</fieldset>
		</div>
		<div class="tab-pane" id="item_material_info">
            <fieldset style="height: 400px;">
                <!-- <div class="form-group form-group-sm">
                    <?php //echo form_label($this->lang->line('raw_orders_add_item'), 'item', array('class'=>'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <?php 
                            // echo form_input(array(
                            //     'name'=>'item',
                            //     'id'=>'item',
                            //     'class'=>'form-control input-sm')
                            // );
                        ?>
                    </div>
                </div> -->
                <ul id="item_error_message_box" class="error_message_box">
                    
                </ul>
                <table id="category_items" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%"><?php echo $this->lang->line('common_delete'); ?></th>
                            <th width="60%"><?php echo $this->lang->line('raw_items_processing_name_available'); ?></th>
                            <th width="30%"><?php echo $this->lang->line('raw_items_processing_quantity'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($raw_item_items as $raw_item_item)
                            {
                            ?>
                                <tr>
                                    <td><a href='#' onclick='return delete_item_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td>
                                    <td><?php echo $raw_item_item['name'].' ['.$raw_item_item['available_quantity'] .']'; ?></td>
                                    <td>
                                        <input class='quantity category_item form-control input-sm' id="category_item_<?php echo $raw_item_item['item_id']; ?>" aria-required='true' aria-invalid='true' max="<?php echo $raw_item_item['available_quantity'] ?>" onkeyup='ValidateFeild($(this))' type='text' name=category_item[<?php echo $raw_item_item['item_id']; ?>] value='1'/>
                                    </td>
                                </tr>
                            <?php
                            }
                        ?>
                    </tbody>
                </table> 
            </fieldset>
        </div>
	</div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{		

	$.validator.setDefaults({ignore: []});

	$('#item_form').validate({
		submitHandler:function(form)
		{
			$(form).ajaxSubmit({
			success:function(response)
			{
				dialog_support.hide();
				table_support.handle_submit('<?php echo site_url('raw_items_processing'); ?>', response);
			},
			dataType:'json'
		});

		},
		errorLabelContainer: "#error_message_box",
 		wrapper: "li",
		rules: 
		{
			newquantity:
			{
				required:true,
				number:true
			}
   		},
		messages: 
		{
			newquantity:
			{
				required:"<?php echo $this->lang->line('items_quantity_required'); ?>",
				number:"<?php echo $this->lang->line('items_quantity_number'); ?>"
			}
		}
	});
});

function fill_quantity(val) 
{   
    var item_quantities = <?php echo json_encode($item_quantities); ?>;
    document.getElementById("quantity").value = parseFloat(item_quantities[val]).toFixed(<?php echo quantity_decimals(); ?>);
}

function ValidateFeild($this) {
    if(Number($this.val())>=1 && Number($this.val())<=Number($this.attr('max'))){
        $($this).parent('td').removeClass('has-error');
    }else{
        $($this).parent('td').addClass('has-error');
    }
}
</script>