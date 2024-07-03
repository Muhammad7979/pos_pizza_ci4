<?php echo form_open('config/save_general', array('id' => 'general_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>
	<div id="config_wrapper">
		<fieldset id="config_info">
			<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>
			<ul id="general_error_messageo_box" class="error_message_box"></ul>

			<div class="form-group form-group-sm">
				<?php echo form_label(lang('config_lang.config_theme'), 'theme', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-3'>
				<?php echo form_dropdown('theme', $themes, $appData['theme'], array('class' => 'form-control input-sm')); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_default_tax_rate_1'), 'default_tax_1_rate', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'default_tax_1_name',
						'id' => 'default_tax_1_name',
						'class' => 'form-control input-sm required',
						'value'=>$appData['default_tax_1_name'])); ?>
				</div>
				<div class="col-xs-1 input-group">
					<?php echo form_input(array(
						'name' => 'default_tax_1_rate',
						'id' => 'default_tax_1_rate',
						'class' => 'form-control input-sm required',
						'value'=>to_tax_decimals($appData['default_tax_1_rate']))); ?>
					<span class="input-group-addon input-sm">%</span>
				</div>
			</div>


			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_default_tax_rate_2'), 'default_tax_2_rate', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'default_tax_2_name',
						'id' => 'default_tax_2_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['default_tax_2_name'])); ?>
				</div>
				<div class="col-xs-1 input-group">
					<?php echo form_input(array(
						'name' => 'default_tax_2_rate',
						'id' => 'default_tax_2_rate',
						'class' => 'form-control input-sm',
						'value'=>to_tax_decimals($appData['default_tax_2_rate']))); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.fbr_fee'), 'FBR Fee ', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'fbr_fee',
						'id' => 'fbr_fee',
						'class' => 'form-control input-sm',
						'value'=>$appData['fbr_fee'])); ?>		
								</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_tax_included'), 'tax_included', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_checkbox(array(
						'name' => 'tax_included',
						'id' => 'tax_included',
						'value'=>$appData['tax_included'],
						'checked'=>$appData['tax_included']==1)); ?>

						
				</div>
			</div>

			

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_default_sales_discount'), 'default_sales_discount', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<div class="input-group">
						<?php echo form_input(array(
							'name' => 'default_sales_discount',
							'id' => 'default_sales_discount',
							'class' => 'form-control input-sm required',
							'type' => 'number',
							'min'=>0,
							'max'=>100,
							'value'=>$appData['default_sales_discount'])); ?>	
						<span class="input-group-addon input-sm">%</span>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_receiving_calculate_average_price'), 'receiving_calculate_average_price', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-1'>
					<?php echo form_checkbox(array(
						'name' => 'receiving_calculate_average_price',
						'id' => 'receiving_calculate_average_price',
						'value'=>$appData['receiving_calculate_average_price']	,
						'checked'=>$appData['receiving_calculate_average_price']==1)); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_lines_per_page'), 'lines_per_page', array('class' => 'control-label col-xs-2 required')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'lines_per_page',
						'id' => 'lines_per_page',
						'class' => 'form-control input-sm required',
						'type' => 'number',
						'min'=>10,
						'max'=>1000,
						'value'=>$appData['lines_per_page'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">
				<?php echo form_label(lang('config_lang.config_notify_alignment'), 'notify_horizontal_position', array('class' => 'control-label col-xs-2')); ?>
				<div class="col-sm-10">
					<div class="form-group form-group-sm row">
						<div class='col-sm-2'>
							<?php echo form_dropdown('notify_vertical_position', array(
								'top' => lang('config_lang.config_top'),
								'bottom' => lang('config_lang.config_bottom')
							),
								$appData['notify_vertical_position'], array('class' => 'form-control input-sm')); ?>
						</div>
						<div class='col-sm-2'>
							<?php echo form_dropdown('notify_horizontal_position', array(
								'left' => lang('config_lang.config_left'),
								'center' => lang('config_lang.config_center'),
								'right' => lang('config_lang.config_right')
							),
								$appData['notify_horizontal_position'], array('class' => 'form-control input-sm')); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom1'), 'config_custom1', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom1_name',
						'id' => 'custom1_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom1_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom2'), 'config_custom2', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom2_name',
						'id' => 'custom2_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom2_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom3'), 'config_custom3', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom3_name',
						'id' => 'custom3_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom3_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom4'), 'config_custom4', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom4_name',
						'id' => 'custom4_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom4_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom5'), 'config_custom5', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom5_name',
						'id' => 'custom5_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom5_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom6'), 'config_custom6', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom6_name',
						'id' => 'custom6_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom6_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom7'), 'config_custom7', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom7_name',
						'id' => 'custom7_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom7_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom8'), 'config_custom8', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom8_name',
						'id' => 'custom8_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom8_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom9'), 'config_custom9', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom9_name',
						'id' => 'custom9_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom9_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_custom10'), 'config_custom10', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<?php echo form_input(array(
						'name' => 'custom10_name',
						'id' => 'custom10_name',
						'class' => 'form-control input-sm',
						'value'=>$appData['custom10_name'])); ?>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_backup_database'), 'config_backup_database', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-2'>
					<div id="backup_db" class="btn btn-default btn-sm">
						<span style="top:22%;"><?php echo lang('config_lang.config_backup_button'); ?></span>
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

	$("#backup_db").click(function() {
		window.location='<?php echo site_url('config/backup_db') ?>';
	});

	$('#general_config_form').validate($.extend(form_support.handler, {

		errorLabelContainer: "#general_error_message_box",

		rules: 
		{
    		default_tax_1_rate:
    		{
    			required: true,
				remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
    		},
			default_tax_1_name: "required",
			default_tax2_rate:
			{
				remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
			},
    		lines_per_page:
    		{
        		required: true,
				remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
    		},
    		default_sales_discount: 
        	{
        		required: true,
				remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
    		}  		
   		},

		messages: 
		{
			default_tax_1_rate:
			{
				required: "<?php echo lang('config_lang.config_default_tax_rate_required'); ?>",
				number: "<?php echo lang('config_lang.config_default_tax_rate_number'); ?>"
			},
			default_tax_1_name:
			{
				required: "<?php echo lang('config_lang.config_default_tax_name_required'); ?>",
				number: "<?php echo lang('config_lang.config_default_tax_name_number'); ?>"
			},
			default_sales_discount:
			{
				required: "<?php echo lang('config_lang.config_default_sales_discount_required'); ?>",
				number: "<?php echo lang('config_lang.config_default_sales_discount_number'); ?>"
			},
			lines_per_page: 
			{
				required: "<?php echo lang('config_lang.config_lines_per_page_required'); ?>",
				number: "<?php echo lang('config_lang.config_lines_per_page_number'); ?>"
			}
		}
	}));
});
</script>