<?php echo form_open('config/save_invoice', array('id' => 'invoice_config_form', 'class' => 'form-horizontal')); ?>
	<div id="config_wrapper">
		<fieldset id="config_info">
			<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>
			<ul id="receipt_error_message_box" class="error_message_box"></ul>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_invoice_enable'), 'invoice_enable', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'invoice_enable',
						'value' => $appData['invoice_enable'],
						'id' => 'invoice_enable',
						'checked' => $appData['invoice_enable']==1));?>
				</div>
			</div>
			
			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_sales_invoice_format'), 'sales_invoice_format', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'sales_invoice_format',
						'id' => 'sales_invoice_format',
						'class' => 'form-control input-sm',
						'value' => $appData['sales_invoice_format'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">    
				<?php echo form_label(lang('config_lang.config_recv_invoice_format'), 'recv_invoice_format', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'recv_invoice_format',
						'id' => 'recv_invoice_format',
						'class' => 'form-control input-sm',
						'value' => $appData['recv_invoice_format'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_invoice_default_comments'), 'invoice_default_comments', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-5'>
					<?php echo form_textarea(array(
						'name' => 'invoice_default_comments',
						'id' => 'invoice_default_comments',
						'class' => 'form-control input-sm',
						'value' => $appData['invoice_default_comments'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_invoice_email_message'), 'invoice_email_message', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-5'>
					<?php echo form_textarea(array(
						'name' => 'invoice_email_message',
						'id' => 'invoice_email_message',
						'class' => 'form-control input-sm',
						'value' => $appData['invoice_email_message'])); ?>
				</div>
			</div>

			<?php echo form_submit(array(
				'name' => 'submit_form',
				'id' => 'submit_form',
				'value' => lang('common_lang.common_submit'),
				'class' => 'btn btn-primary btn-sm pull-right'));?>
		</fieldset>
	</div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	var enable_disable_invoice_enable = (function() {
		var invoice_enable = $("#invoice_enable").is(":checked");
		$("#sales_invoice_format, #recv_invoice_format, #invoice_default_comments, #invoice_email_message").prop("disabled", !invoice_enable);
		return arguments.callee;
	})();

	$("#invoice_enable").change(enable_disable_invoice_enable);

	$("#invoice_config_form").validate($.extend(form_support.handler, {
		submitHandler: function(form) {
			$(form).ajaxSubmit({
				beforeSerialize: function(arr, $form, options) {
					$("#sales_invoice_format, #recv_invoice_format, #invoice_default_comments, #invoice_email_message").prop("disabled", false); 
					return true;
				},
				success: function(response) {
					$.notify(response.message, { type: response.success ? 'success' : 'danger'} );
					// set back disabled state
					enable_disable_invoice_enable();
				},
				dataType:'json'
			});
		},

		errorLabelContainer: "#receipt_error_message_box"
	}));
});
</script>