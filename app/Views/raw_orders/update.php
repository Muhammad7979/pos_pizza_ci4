<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

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

<?php echo form_open('raw_orders/update/'.$raw_order_info->order_id, array('id'=>'raw_order_form', 'class'=>'form-horizontal')); ?>
	<fieldset id="raw_order_basic_info" <?php echo $style; ?>>
		
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

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_delivered_description'), 'delivered_description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'delivered_description',
						'id'=>'delivered_description',
						'class'=>'form-control input-sm',
						'value'=>$raw_order_info->delivered_description)
						);?>
			</div>
		</div>

		<table id="raw_order_items" class="table table-striped table-hover">
			<thead>
				<tr>
					<th width="70%"><?php echo lang('raw_order_lang.raw_orders_item'); ?></th>
					<th width="20%"><?php echo lang('raw_order_lang.raw_orders_quantity'); ?></th>
					<th width="20%"><?php echo lang('raw_order_lang.raw_orders_quantity_deliver'); ?></th>
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

						<td><input class='quantity form-control input-sm' id='raw_order_item_<?php echo $raw_order_item['item_id'] ?>' onkeyup='ValidateFeild($(this) , <?php echo parse_decimals($raw_order_item['quantity']) ?>)' name=raw_order_item_update[<?php echo $raw_order_item['item_id'] ?>] value='<?php echo (isset($raw_order_item['delivered_quantity']) && $raw_order_item['delivered_quantity'] > 0) ? parse_decimals($raw_order_item['delivered_quantity']) : parse_decimals($raw_order_item['quantity']) ?>'/></td>

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
	                table_support.handle_submit('<?php echo site_url('raw_orders'); ?>', response);
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
	        	"raw_order_item_update[<?php echo $order_item['item_id'] ?>]": {
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
	        	"raw_order_item_update[<?php echo $order_item['item_id'] ?>]": {
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