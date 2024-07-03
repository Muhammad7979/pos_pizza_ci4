<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>
<?php echo form_open('raw_orders/save/'.$raw_order_info->order_id, array('id'=>'raw_order_form', 'class'=>'form-horizontal')); ?>
	<fieldset id="raw_order_basic_info">
		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_category'), 'category', array('class'=>'required control-label col-xs-3')); ?>
			<div class="col-xs-8">
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
							'value'=>1,
							'checked'=>$raw_order_info->category === '1')
							); ?> <?php echo lang('common_lang.common_category_vendor'); ?>
				</label>
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
							'value'=>2,
							'checked'=>$raw_order_info->category === '2')
							); ?> <?php echo lang('common_lang.common_category_warehouse'); ?>
				</label>
				<label class="radio-inline">
					<?php echo form_radio(array(
							'name'=>'category',
							'type'=>'radio',
							'id'=>'category',
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
					if($selected_company==0){
						$array = array('id' => 'companies', 'class' => 'form-control', 'disabled' => 'disabled');
					}else{
						$array = array('id' => 'companies', 'class' => 'form-control');
					}
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
						'class'=>'form-control input-sm',
						'value'=>$raw_order_info->description)
						);?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('raw_order_lang.raw_orders_add_item'), 'item', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php 
					if ($selected_company==0) {
						echo form_input(array(
							'name'=>'item',
							'id'=>'item',
							'placeholder'=>'Search item',
							'disabled'=>'disabled',
							'class'=>'form-control input-sm')
							);
					}else{
						echo form_input(array(
							'name'=>'item',
							'id'=>'item',
							'placeholder'=>'Search item',
							'class'=>'form-control input-sm')
							);
					}
				?>
			</div>
		</div>

		<table id="raw_order_items" class="table table-striped table-hover">
			<thead>
				<tr>
					<th width="10%"><?php echo lang('common_lang.common_delete'); ?></th>
					<th width="70%"><?php echo lang('raw_order_lang.raw_orders_item'); ?></th>
					<th width="20%"><?php echo lang('raw_order_lang.raw_orders_quantity'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($raw_order_items as $raw_order_item)
				{
				?>
					<tr>
						<td><a href='#' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td>
						<td><?php echo $raw_order_item['name']; ?></td>
						<td><input class='quantity form-control input-sm' id='raw_order_item_<?php echo $raw_order_item['item_id'] ?>' name=raw_order_item[<?php echo $raw_order_item['item_id'] ?>] value='<?php echo parse_decimals($raw_order_item['quantity']) ?>'/></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
     <?php echo form_input(array('name'=>'company_email','type'=>'hidden','id'=>'company_email','value'=>'')); ?>

	</fieldset>
<?php echo form_close(); ?>

<?php echo form_input(array('name'=>'category_selected','type'=>'hidden','id'=>'category_selected','value'=>$category_selected)); ?>
<?php echo form_input(array('name'=>'company_selected','type'=>'hidden','id'=>'company_selected','value'=>$selected_company)); ?>



<script type="text/javascript">

//validation and submit handling
$(document).ready(function()
{
	$('input:radio[name="category"]').change(function(){
	   	var category = $(this).val();
	   	$('#category_selected').val(category);
        $.getJSON("<?php echo base_url('raw_orders/suggest_companies/');?>"+category, function (data) {
			console.log(data);
        	$('#item').attr('disabled',true);
        	$('#item').val('');
        	$('#raw_order_items tbody').html('');
        	var dropdown = $('#companies');
			dropdown.removeAttr('disabled');
			dropdown.empty();
		    $.each(data, function (index, item) {
		        dropdown.append(
		            $('<option></option>').val(index).html(item.name).attr('data-email', item.email)
		        );
		    });
		});

	});

	$('#companies').change(function(){
		if($(this).val()){
			$('#company_selected').val($(this).val());
			// var selectedOption = $(this).find(':selected');
			$('#company_email').val($(this).find(':selected').data('email'));
			$('#item').removeAttr('disabled');
		}else{
			$('#company_selected').val('');
			$('#item').attr('disabled',true);
			$('#item').val('');
		}
		$('#raw_order_items tbody').html('');
	});
	$('#item').focus(function(){


$("#item").autocomplete({
	source: '<?php echo base_url(); ?>'+'raw_orders/suggest?category='+$('#category_selected').val()+'&person_id='+$('#company_selected').val(),
	minChars:0,
	minLength: 0,
	autoFocus: false,
	delay:10,
	appendTo: ".modal-content",
	select: function(e, ui) {
console.log(ui);
        if(ui.item.label === 'No item'){
			$("#item").val("");
		    $("#item").blur();
		    return false;
		}
		if ($("#raw_order_item_" + ui.item.value).length == 1)
		{
			$("#raw_order_item_" + ui.item.value).val(parseFloat( $("#raw_order_item_" + ui.item.value).val()) + 1);
		}
		else
		{
			$("#raw_order_items").append("<tr><td><a href='#' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td><td>" + ui.item.label + "</td><td><input class='quantity form-control input-sm' id='raw_order_item_" + ui.item.value + "' max='" + ui.item.max + "' aria-required='true' aria-invalid='true' onkeyup='ValidateFeild($(this))' type='text' name=raw_order_item[" + ui.item.value + "] value='1'/></td></tr>");

			$("#raw_order_item_"+ui.item.value).rules('add', {
				required: true,
				number: true,
				min: 1,
				max: ui.item.max,
				messages: {
					required: "Item Quantity/Scale is required",
					number: "Enter a valid Number",
					min: "Quantity must be greater or equel to 1",
					max: "Quantity must be less or equel to "+ui.item.max,
				}
			});
			$('#item').rules("remove", "required");
			
		}

		$("#item").val("");
		$("#item").blur();
		return false;
	}
}).autocomplete('search', '');
});

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
			category:"required",
			person_id: {
                required: true,
                min: 1
            },
            item: {
                required: true,
            },
		},
		messages:
		{
			category:"<?php echo lang('itens_lang.items_category_required'); ?>",
			person_id: {
                required: "<?php echo lang('raw_order_lang.raw_orders_company_name_required'); ?>",
                min: "<?php echo lang('raw_order_lang.raw_orders_company_name_required'); ?>"
            },
            item: {
                required: "<?php echo lang('raw_order_lang.raw_orders_items_required'); ?>",
            },
		}
	}, form_support.error));
});

function delete_raw_order_row(link)
{
	$(link).parent().parent().remove();
	var i= 0;
    $('input.quantity').each(function () {
	    i = i+1;
	});
    if(i<1){
    	$("#item").rules('add', {
	        required: true,
	        messages: {
                required: "<?php echo lang('raw_order_lang.raw_orders_items_required'); ?>",
            }
	    });

    }
	return false;
}

function ValidateFeild($this) {
	if(Number($this.val())>=1 && Number($this.val())<=Number($this.attr('max'))){
		$($this).parent('td').removeClass('has-error');
	}else{
		$($this).parent('td').addClass('has-error');
	}
}

</script>