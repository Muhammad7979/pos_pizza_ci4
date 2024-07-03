<?php echo form_open('config/save_receipt', array('id' => 'receipt_config_form', 'class' => 'form-horizontal')); ?>
	<div id="config_wrapper">
		<fieldset id="config_info">
			<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>
			<ul id="receipt_error_message_box" class="error_message_box"></ul>

			<div class="form-group form-group-sm">
				<?php echo form_label(lang('config_lang.config_receipt_template'), 'receipt_template', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_dropdown('receipt_template', array(
						'receipt_default' => lang('config_lang.config_receipt_default'),
						'receipt_short' => lang('config_lang.config_receipt_short')
					),
				   $appData['receipt_template'], array('class' => 'form-control input-sm'));
					?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receipt_show_taxes'), 'receipt_show_taxes', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'receipt_show_taxes',
						'value' => $appData['receipt_show_taxes'],
						'id' => 'receipt_show_taxes',
						'checked'=>$appData['receipt_show_taxes']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receipt_show_total_discount'), 'receipt_show_total_discount', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'receipt_show_total_discount',
						'value' => $appData['receipt_show_total_discount'],
						'id' => 'receipt_show_total_discount',
						'checked'=>$appData['receipt_show_total_discount']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receipt_show_description'), 'receipt_show_description', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'receipt_show_description',
						'value' => $appData['receipt_show_description'],
						'id' => 'receipt_show_description',
						'checked'=>$appData['receipt_show_description']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receipt_show_serialnumber'), 'receipt_show_serialnumber', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'receipt_show_serialnumber',
						'value' => $appData['receipt_show_serialnumber'],
						'id' => 'receipt_show_serialnumber',
						'checked'=>$appData['receipt_show_serialnumber']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_print_silently'), 'print_silently', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'print_silently',
						'id' => 'print_silently',
						'value' => $appData['print_silently'],
						'checked'=>$appData['print_silently']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_print_header'), 'print_header', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'print_header',
						'id' => 'print_header',
						'value' => $appData['print_header'],
						'checked'=>$appData['print_header']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_print_footer'), 'print_footer', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'print_footer',
						'id' => 'print_footer',
						'value' => $appData['print_footer'],
						'checked'=>$appData['print_footer']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receipt_printer'), 'config_receipt_printer', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_dropdown('receipt_printer',	array(), ' ', 'id="receipt_printer" class="form-control"'); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_invoice_printer'), 'config_invoice_printer', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_dropdown('invoice_printer', array(), ' ', 'id="invoice_printer" class="form-control"'); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_takings_printer'), 'config_takings_printer', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_dropdown('takings_printer', array(), ' ', 'id="takings_printer" class="form-control"'); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_print_top_margin'), 'print_top_margin', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<div class="input-group">
						<?php echo form_input(array(
							'type' => 'number',
							'min' => '0',
							'max' => '20',
							'name' => 'print_top_margin',
							'id' => 'print_top_margin',
							'class' => 'form-control input-sm required',
							'value'=>config('config_lang.print_top_margin'))); ?>
						<span class="input-group-addon input-sm">px</span>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_print_left_margin'), 'print_left_margin', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<div class="input-group">
						<?php echo form_input(array(
							'type' => 'number',
							'min' => '0',
							'max' => '20',
							'name' => 'print_left_margin',
							'id' => 'print_left_margin',
							'class' => 'form-control input-sm required',
							'value'=>config('config_lang.print_left_margin'))); ?>
						<span class="input-group-addon input-sm">px</span>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_print_bottom_margin'), 'print_bottom_margin', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<div class="input-group">
						<?php echo form_input(array(
							'type' => 'number',
							'min' => '0',
							'max' => '20',
							'name' => 'print_bottom_margin',
							'id' => 'print_bottom_margin',
							'class' => 'form-control input-sm required',
							'value'=>config('config_lang.print_bottom_margin'))); ?>
						<span class="input-group-addon input-sm">px</span>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_print_right_margin'), 'print_right_margin', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<div class="input-group">
						<?php echo form_input(array(
							'type' => 'number',
							'min' => '0',
							'max' => '20',
							'name' => 'print_right_margin',
							'id' => 'print_right_margin',
							'class' => 'form-control input-sm required',
							'value'=>config('config_lang.print_right_margin'))); ?>
						<span class="input-group-addon input-sm">px</span>
					</div>
				</div>
			</div>

			<?php echo form_submit(array(
				'name' => 'submit_form',
				'id' => 'submit_form',
				'value'=>lang('common_lang.common_submit'),
				'class' => 'btn btn-primary btn-sm pull-right')); ?>
		</fieldset>
	</div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	if (window.localStorage && window.jsPrintSetup) 
	{
		var printers = (jsPrintSetup.getPrintersList() && jsPrintSetup.getPrintersList().split(',')) || [];
		$('#receipt_printer, #invoice_printer, #takings_printer').each(function() 
		{
			var $this = $(this)
			$(printers).each(function(key, value) 
			{   
			     $this.append($('<option>', { value : value }).text(value));
  	 		});
			$("option[value='" + localStorage[$(this).attr('id')] + "']", this).prop('selected', true);
			$(this).change(function()
			{
				localStorage[$(this).attr('id')] = $(this).val();		
			});
		});
	}
	else
	{
		$("input[id*='margin'], #print_footer, #print_header, #receipt_printer, #invoice_printer, #takings_printer, #print_silently").prop('disabled', true);
		$("#receipt_printer, #invoice_printer, #takings_printer").each(function() 
		{
			$(this).append($('<option>', {value : 'na'}).text('N/A'));
		});
	}

	var dialog_confirmed = window.jsPrintSetup;
			
	$('#receipt_config_form').validate($.extend(form_support.handler, {
		submitHandler: function(form) {
			$(form).ajaxSubmit({
				beforeSerialize: function(arr, $form, options) {
					return ( dialog_confirmed || confirm('<?php echo lang('config_lang.config_jsprintsetup_required'); ?>') );
				},
				success: function(response) {
					$.notify(response.message, { type: response.success ? 'success' : 'danger'} );
				},
				dataType:'json'
			});
		},

		errorLabelContainer: "#receipt_error_message_box",

		rules:
		{
			print_top_margin:
    		{
    			required:true,
    			number:true
    		},
    		print_left_margin:
    		{
    			required:true,
    			number:true
    		},
    		print_bottom_margin:
    		{
    			required:true,
    			number:true
    		},
    		print_right_margin:
    		{
    			required:true,
    			number:true
    		}    		
   		},

		messages: 
		{
			print_top_margin:
			{
	            required:"<?php echo lang('config_lang.config_print_top_margin_required'); ?>",
	            number:"<?php echo lang('config_lang.config_print_top_margin_number'); ?>"
			},
			print_left_margin:
			{
	            required:"<?php echo lang('config_lang.config_print_left_margin_required'); ?>",
	            number:"<?php echo lang('config_lang.config_print_left_margin_number'); ?>"
			},
			print_bottom_margin:
			{
	            required:"<?php echo lang('config_lang.config_print_bottom_margin_required'); ?>",
	            number:"<?php echo lang('config_lang.config_print_bottom_margin_number'); ?>"
			},
			print_right_margin:
			{
	            required:"<?php echo lang('config_lang.config_print_right_margin_required'); ?>",
	            number:"<?php echo lang('config_lang.config_print_right_margin_number'); ?>"
			}
		}
	}));
});
</script>