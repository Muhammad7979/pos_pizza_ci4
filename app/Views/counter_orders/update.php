<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php
/**
 * disable non editable items for local
 */
if($counter_order_info->is_received == 1 && $counter_order_info->is_received != '') {
    $style = "class='disabled' disabled";
    echo "<b style='color:red;'>This Order cannot be modified.</b> <hr/>";
    echo "<script>$('#submit').hide();</script>";
}
else {
    $style = "";
}
?>

<?php echo form_open('counter_orders/update/'.$counter_order_info->order_id, array('id'=>'counter_order_form', 'class'=>'form-horizontal')); ?>
	<fieldset id="counter_order_basic_info" <?php echo $style; ?>>
		
		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('counter_orders_description'), 'description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'description',
						'id'=>'description',
						'disabled' => 'disabled',
						'class'=>'form-control input-sm',
						'value'=>$counter_order_info->description)
						);?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('counter_orders_delivered_description'), 'delivered_description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'delivered_description',
						'id'=>'delivered_description',
						'class'=>'form-control input-sm',
						'value'=>$counter_order_info->delivered_description)
						);?>
			</div>
		</div>

		<table id="counter_order_items" class="table table-striped table-hover">
			<thead>
				<tr>
					<th width="70%"><?php echo $this->lang->line('counter_orders_item'); ?></th>
					<th width="20%"><?php echo $this->lang->line('counter_orders_quantity'); ?></th>
					<th width="20%"><?php echo $this->lang->line('counter_orders_quantity_deliver'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($counter_order_items as $counter_order_item)
				{
				?>
					<tr>
						<td><?php echo $counter_order_item['name']; ?></td>
						<td><input class='quantity form-control input-sm' id='counter_order_item_<?php echo $counter_order_item['item_id'] ?>' disabled="disabled" name=counter_order_item[<?php echo $counter_order_item['item_id'] ?>] value='<?php echo parse_decimals($counter_order_item['quantity']) ?>'/></td>

						<td><input class='quantity form-control input-sm' id='counter_order_item_<?php echo $counter_order_item['item_id'] ?>' onkeyup='ValidateFeild($(this) , <?php echo parse_decimals($counter_order_item['quantity']) ?>)' name=counter_order_item_update[<?php echo $counter_order_item['item_id'] ?>] value='<?php echo (isset($counter_order_item['delivered_quantity']) && $counter_order_item['delivered_quantity'] > 0) ? parse_decimals($counter_order_item['delivered_quantity']) : parse_decimals($counter_order_item['quantity']) ?>'/></td>

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
	$('#counter_order_form').validate($.extend({
		submitHandler:function(form)
		{
			$('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
            $('#submit').hide();
			$(form).ajaxSubmit({
				success: function (response) {
	                dialog_support.hide();
	                table_support.handle_submit('<?php echo site_url('counter_orders'); ?>', response);
	                //console.log('done');
	            },
				dataType:'json'
			});

		},
		rules:
		{
			<?php
	        foreach($counter_order_items as $order_item)
	        {
	        ?>
	        	"counter_order_item_update[<?php echo $order_item['item_id'] ?>]": {
	        		required: true,
	        		number: true,
	        		max: <?php echo $order_item['quantity'] ?>,
	        	},
	        <?php
	        }
	        ?>
		},
		messages:
		{
			<?php
	        foreach($counter_order_items as $order_item)
	        {
	        ?>
	        	"counter_order_item_update[<?php echo $order_item['item_id'] ?>]": {
	        		required: "Item Quantity/Scale is required",
	        		number: "Item Quantity/Scale is not a number",
	        		max: "Quantity/Scale must be less than <?php echo $order_item['quantity'] ?>",
	        	},
	        <?php
	        }
	        ?>
		}
	}, form_support.error));
});

function delete_counter_order_row(link)
{
	$(link).parent().parent().remove();
	return false;
}
function ValidateFeild($this, max) {

	if($this.val()<0 || $this.val()>max || $this.val()=='' || !$.isNumeric($this.val())){
		$($this).parent('td').addClass('has-error');
	}else{
		$($this).parent('td').removeClass('has-error');
	}
}

</script>