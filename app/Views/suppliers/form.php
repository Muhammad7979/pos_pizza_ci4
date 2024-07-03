<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('suppliers/save/' . $person_id, array('id' => 'supplier_form', 'class' => 'form-horizontal')); ?>
<fieldset id="supplier_basic_info">
    <?php if ($selected_person_id){?>
<div class="form-group form-group-sm">
        <?php echo form_label(lang('sales_lang.sales_employee'), 'employee', array('class' => 'control-label col-xs-3')); ?>
        <div class='col-xs-8'>
            <?php
            $employee_options = array();
            foreach ($employees as $employee) {
                $employee_options[$employee['person_id']] = $employee['first_name']; // Replace 'employee_name' with the actual column name
            }
            echo form_dropdown(
                'employee_id',
                $employee_options,
                $selected_person_id, // $selected_employee_id should be set with the selected employee's ID, if editing
                'class="form-control" disabled',
				
			);
            ?>
			<?php echo form_hidden('person_id', $selected_person_id);?>

        </div>
   

</div>
<?php }else{ ?>
	<div class="form-group form-group-sm">
    <?php echo form_label(lang('sales_lang.sales_employee'), 'employee', array('class' => 'control-label col-xs-3')); ?>
    <div class="col-xs-8">
        <?php
        $employee_options = [];
        foreach ($employees as $employee) {
            $employee_options[$employee['person_id']] = $employee['first_name'];
        }

        $selected_employee_id = ''; // Initialize the selected employee ID (for editing)
        // Assuming you have a $selected_employee variable that holds the selected employee's data
        if (!empty($selected_employee)) {
            $selected_employee_id = $selected_employee['person_id'];
        }

        echo form_dropdown(
            'employee_id',
            $employee_options,
            $selected_employee_id, // Use the selected employee ID here
            'class="form-control"'
        );
        ?>
        <!-- This should send the correct person_id -->
    </div>
</div>
</div>

   

</div>
		<?php  }?>

	<div class="form-group form-group-sm">
		<?php echo form_label(lang('suppliers_lang.suppliers_company_name'), 'company_name', array('class' => 'required control-label col-xs-3')); ?>
		<div class='col-xs-8'>
			<?php echo form_input(
				array(
					'name' => 'company_name',
					'id' => 'company_name_input',
					'class' => 'form-control input-sm',
					'value' => $company_name
				)
			); ?>
		</div>
	</div>

	<div class="form-group form-group-sm">
		<?php echo form_label(lang('suppliers_lang.suppliers_agency_name'), 'agency_name', array('class' => 'control-label col-xs-3')); ?>
		<div class='col-xs-8'>
			<?php echo form_input(
				array(
					'name' => 'agency_name',
					'id' => 'agency_name_input',
					'class' => 'form-control input-sm',
					'value' => $agency_name
				)
			); ?>
		</div>
	</div>


	<div class="form-group form-group-sm">
		<?php echo form_label(lang('suppliers_lang.suppliers_account_number'), 'account_number', array('class' => 'control-label col-xs-3')); ?>
		<div class='col-xs-8'>
			<?php echo form_input(
				array(
					'name' => 'account_number',
					'id' => 'account_number',
					'class' => 'form-control input-sm',
					'value' => $account_number
				)
			); ?>
		</div>
	</div>
</fieldset>
<?php echo form_close(); ?>

<script type="text/javascript">
	//validation and submit handling
	$(document).ready(function() {
		$('#supplier_form').validate($.extend({
			submitHandler: function(form) {
				$(form).ajaxSubmit({
					success: function(response) {
						dialog_support.hide();
						table_support.handle_submit('<?php echo site_url('suppliers'); ?>', response);
					},
					dataType: 'json'
				});

			},
			rules: {
				company_name: "required",

				email: "email"
			},
			messages: {
				company_name: "<?php echo lang('suppliers_company_name_required'); ?>",

				email: "<?php echo lang('common_email_invalid_format'); ?>"
			}
		}, form_support.error));
	});
</script>