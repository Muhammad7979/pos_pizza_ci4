<div id="required_fields_message">
	<?php //echo (isset($raw_order_info->is_received) && $raw_order_info->is_received==1)? 'disabled="disabled"' : '' ?>
	<?php echo lang('common_lang.common_fields_required_message'); ?>	
</div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php
/**
 * disable non editable items for local
 */
if($raw_order_info->is_received == 1 && $raw_order_info->is_received != '') {
    $style = "class='disabled' disabled";
    echo "<b style='color:red;'>This Order cannot be modified.</b> <hr/>";
    echo "<script>$('#submit').hide();</script>";
}
else {
    $style = "";
}
?>

<?php echo form_open('raw_orders/update_receiving/'.$raw_order_info->order_id, array('id'=>'raw_order_form', 'class'=>'form-horizontal')); ?>
	<fieldset id="raw_order_basic_info" <?php echo $style; ?>>
		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_category'), 'category', array('class'=>'required control-label col-xs-3')); ?>
			<div class="col-xs-8">
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
							'disabled' => 'disabled',
							'value'=>1,
							'checked'=>$raw_order_info->category === '1')
							); ?> <?php echo lang('common_lang.common_category_vendor'); ?>
				</label>
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
							'disabled' => 'disabled',
							'value'=>2,
							'checked'=>$raw_order_info->category === '2')
							); ?> <?php echo lang('common_lang.common_category_warehouse'); ?>
				</label>
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
							'disabled' => 'disabled',
							'value'=>3,
							'checked'=>$raw_order_info->category === '3')
							); ?> <?php echo lang('common_lang.common_category_store'); ?>
				</label>
			</div>
		</div>
	
		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_company_name'), 'person_id', array('class'=>'required control-label col-xs-3')); ?>
			<div class="col-xs-8">
				<?php 
					$array = array('id' => 'companies', 'class' => 'form-control', 'disabled' => 'disabled');
				?>
				<?php echo form_dropdown('person_id', $companies, $selected_company, $array); ?>
			</div>
		</div>
		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_description'), 'description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'description',
						'id'=>'description',
						'disabled' => 'disabled',
						'class'=>'form-control input-sm',
						'value'=>$raw_order_info->description)
						);?>
			</div>
		</div>

		<!-- <div class="form-group form-group-sm">
			<?php //echo form_label(lang('raw_order_lang.raw_orders_delivered_description'), 'delivered_description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php //echo form_textarea(array(
						//'name'=>'delivered_description',
						//'id'=>'delivered_description',
						//'disabled'=>'disabled',
						//'class'=>'form-control input-sm',
						//'value'=>$raw_order_info->delivered_description)
						//);?>
			</div>
		</div> -->

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_received_description'), 'receiving_description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'receiving_description',
						'id'=>'receiving_description',
						'class'=>'form-control input-sm',
						'value'=>$raw_order_info->receiving_description)
						);?>
			</div>
		</div>
		<?php
			if ($raw_order_info->is_received==1) {
				# code...
			}
		?>
		<table id="raw_order_items" class="table table-striped table-hover">
			<thead>
				<tr>
					<th width="60%"><?php echo lang('raw_order_lang.raw_orders_item'); ?></th>
					<th width="20%"><?php echo lang('raw_order_lang.raw_orders_quantity'); ?></th>
					<!-- <th width="20%"><?php //echo lang('raw_order_lang.raw_orders_quantity_deliver'); ?></th> -->
					<th width="20%"><?php echo lang('raw_order_lang.raw_orders_quantity_received'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($raw_order_items as $raw_order_item)
				{
				?>
					<tr>
						<td><?php echo $raw_order_item['name']; ?></td>
						<td><input class='quantity form-control input-sm' id='raw_order_item_<?php echo $raw_order_item['item_id'] ?>' disabled="disabled" name=raw_order_item[<?php echo $raw_order_item['item_id'] ?>] value='<?php echo parse_decimals($raw_order_item['quantity']) ?>'/></td>
						<!-- <td><input class='quantity form-control input-sm' id='raw_order_item_<?php //echo $raw_order_item['item_id'] ?>' disabled="disabled" name=raw_order_item_update[<?php //echo $raw_order_item['item_id'] ?>] value='<?php //echo parse_decimals($raw_order_item['delivered_quantity']) ?>'/></td> -->
						<td><input class='quantity form-control input-sm' id='raw_order_item_<?php echo $raw_order_item['item_id'] ?>' onkeyup='ValidateFeild($(this) , <?php echo parse_decimals($raw_order_item['quantity']) ?>)' name=raw_order_item_received[<?php echo $raw_order_item['item_id'] ?>] value='<?php echo (isset($raw_order_item['received_quantity']) && $raw_order_item['received_quantity'] > 0) ? parse_decimals($raw_order_item['received_quantity']) : parse_decimals($raw_order_item['quantity']) ?>'/></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>

<?php echo form_close(); ?>




<script type="text/javascript">

//validation and submit handling
$(document).ready(function()
{
	$('#raw_order_form').validate($.extend({
		submitHandler:function(form)
		{
			$('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
            $('#submit').hide();
			$(form).ajaxSubmit({
				success: function (response) {
	                dialog_support.hide();
	                table_support.handle_submit('<?php echo base_url('raw_orders'); ?>', response);
	                //console.log('done');
	            },
				dataType:'json'
			});

		},
		rules:
		{
			<?php
	        foreach($raw_order_items as $order_item)
	        {
	        ?>
	        	"raw_order_item_received[<?php echo $order_item['item_id'] ?>]": {
	        		required: true,
	        		number: true,
	        	},
	        <?php
	        }
	        ?>
		},
		messages:
		{
			<?php
	        foreach($raw_order_items as $order_item)
	        {
	        ?>
	        	"raw_order_item_received[<?php echo $order_item['item_id'] ?>]": {
	        		required: "Item Quantity/Scale is required",
	        		number: "Item Quantity/Scale is not a number",
	        	},
	        <?php
	        }
	        ?>
		}
	}, form_support.error));
});

function delete_raw_order_row(link)
{
	$(link).parent().parent().remove();
	return false;
}

function ValidateFeild($this, max) {

	if($this.val()<0 || $this.val()=='' || !$.isNumeric($this.val())){
		$($this).parent('td').addClass('has-error');
	}else{
		$($this).parent('td').removeClass('has-error');
	}
}
</script>