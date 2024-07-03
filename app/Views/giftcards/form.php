<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php
/**
 * disable non editable items for local
 */

if(($giftcard_expires && strtotime('now') > strtotime($giftcard_expires)) || ($giftcard_status && $giftcard_status==1)) {
    $style = "class='disabled' disabled";
    echo "<b style='color:red;'>This Giftcard cannot be modified.</b> <hr/>";
    echo "<script>$('#submit').hide();</script>";
}
else {
    $style = "";
}
?>
	
<?php echo form_open('giftcards/save/'.$giftcard_id, array('id'=>'giftcard_form', 'class'=>'form-horizontal')); ?>
	<fieldset id="giftcard_basic_info" <?php echo $style; ?>>
		<!-- <div class="form-group form-group-sm">
			<?php //echo form_label(lang('giftcards_person_id'), 'name', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php //echo form_input(array(
						//'name'=>'person_name',
						//'id'=>'person_name',
						//'class'=>'form-control input-sm',
						//'value'=>$selected_person_name)
						//);?>
				<?php //echo form_hidden('person_id', $selected_person_id);?>
			</div>
		</div> -->

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('giftcards_lang.giftcards_giftcard_number'), 'name', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-4'>
				<?php echo form_input(array(
						'name'=>'giftcard_number',
						'id'=>'giftcard_number',
						'class'=>'form-control input-sm',
						'value'=>$giftcard_number)
						);?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('giftcards_lang.giftcards_card_value'), 'name', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-4'>
				<div class="input-group input-group-sm">
				
					<span class="input-group-addon input-sm"><b><?php echo $appData['currency_symbol']; ?></b></span>
					
					<?php echo form_input(array(
							'name'=>'value',
							'id'=>'value',
							'class'=>'form-control input-sm',
							'value'=>to_currency_no_money($giftcard_value))
							);?>
					
				
				</div>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label(lang('giftcards_lang.giftcards_card_expired'), 'expires_at', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-4'>
				<?php if (isset($giftcard_expires) && !empty($giftcard_expires)) { ?>
					<?php echo form_input(array('name' => 'expires_at', 'class' => 'form-control input-sm', 'id' => '', 'value'=>$giftcard_expires)); ?>
				<?php }else{ ?>
					<?php echo form_input(array('name' => 'expires_at', 'class' => 'form-control input-sm', 'id' => 'expires_at')); ?>
				<?php } ?>
			</div>
		</div>

	</fieldset>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{	
	var today = new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());

	$('#expires_at').daterangepicker({
	    singleDatePicker: true,
	    minDate: today,
	    locale: {
	      format: 'YYYY-MM-DD 23:59:59'
	    }
	}, function(start, end, label) {
	  console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
	});

	$("input[name='person_name']").change(function() {
		if( ! $("input[name='person_name']").val() ) {
			$("input[name='person_id']").val('');
		}
	});
	
	var fill_value = function(event, ui) {
		event.preventDefault();
		$("input[name='person_id']").val(ui.item.value);
		$("input[name='person_name']").val(ui.item.label);
	};

	var autocompleter = $("#person_name").autocomplete({
		source: '<?php echo site_url("customers/suggest"); ?>',
    	minChars: 0,
    	delay: 15, 
       	cacheLength: 1,
		appendTo: '.modal-content',
		select: fill_value,
		focus: fill_value
    });

	// declare submitHandler as an object.. will be reused
	var submit_form = function() { 
		$(this).ajaxSubmit({
			success: function(response)
			{
				dialog_support.hide();
				table_support.handle_submit('<?php echo site_url($controller_name); ?>', response);
			},
			error: function(jqXHR, textStatus, errorThrown) 
			{
				table_support.handle_submit('<?php echo site_url($controller_name); ?>', {message: errorThrown});
			},
			dataType:'json'
		});
	};
	
	$('#giftcard_form').validate($.extend({
		submitHandler:function(form)
		{
			submit_form.call(form)
		},
		rules:
		{
			giftcard_number:
			{
				required:true,
				remote: {
                    url: "<?php echo site_url($controller_name . '/check_giftcard_number')?>",
                    type: "post",
                    data: $.extend(csrf_form_base(),
                    {
                        "giftcard_id": "<?php echo $giftcard_id; ?>",
                        "giftcard_number": function () {
                            return $("#giftcard_number").val();
                        },
                    })
                }
			},
			value:
			{
				required:true,
				number:true
			},
			expires_at:
			{
				required:true,
			}
   		},
		messages:
		{
			giftcard_number:
			{
				required:"<?php echo lang('giftcards_lang.giftcards_number_required'); ?>",
				remote:"<?php echo lang('giftcards_lang.giftcards_number_duplicate'); ?>"
			},
			value:
			{
				required:"<?php echo lang('giftcards_lang.giftcards_value_required'); ?>",
				number:"<?php echo lang('giftcards_lang.giftcards_value'); ?>"
			},
			expires_at:
			{
				required:"<?php echo lang('giftcards_lang.giftcards_card_expired_required'); ?>",
			}
		}
	}, form_support.error));
});


</script>