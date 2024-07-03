<!-- 	<link rel='stylesheet' href='<?php //echo base_url('dist/bootstrap-tagsinput.css'); ?>'>
    <script src='<?php //echo base_url('dist/bootstrap3-typeahead.min.js'); ?>'></script>
    <script src='<?php //echo base_url('dist/bootstrap-tagsinput.js'); ?>'></script> -->

<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>



<?php  echo form_open('categories/save/'.$category_info->item_id, array('id'=>'category_form', 'class'=>'form-horizontal')); ?>
    <ul class="nav nav-tabs nav-justified" data-tabs="tabs">
        <li class="active" role="presentation">
            <a data-toggle="tab"
               href="#items_basic_info"><?php echo lang("categories_lang.items_information"); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#items_attribute_info"><?php echo lang("categories_lang.items_attribute"); ?></a>
        </li>
       
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade in active" id="items_basic_info">

        	<fieldset>

                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('items_lang.items_item_number'), 'item_number', array('class' => 'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <div class="input-group">
                            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-barcode"></span></span>
                            <?php echo form_input(array(
                                    'name' => 'item_number',
                                    'id' => 'item_number',
                                    'class' => 'form-control input-sm',
                                    'value' => $category_info->item_number)
                            ); ?>
                        </div>
                    </div>
                </div>

        		<div class="form-group form-group-sm">
                    <?php echo form_label(lang('categories_lang.category_title'), 'category', array('class' => 'required control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <!-- drop down for unique item categories -->
                        <!-- <?php //echo form_dropdown('category_title', $categories, $selected_category, array('class' => 'form-control')); ?> -->
                        <!-- Suggestions for item categories -->
                        <div class="input-group">
                            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-tag"></span></span>
                            <?php echo form_input(array(
                                    'name' => 'category',
                                    'id' => 'category',
                                    'class' => 'form-control input-sm',
                                    'value' => $category_info->category)
                            ); ?>
                        </div>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('categories_lang.category_name'), 'category_name', array('class' => 'required control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <?php echo form_input(array(
                                'name' => 'category_name',
                                'id' => 'category_name',
                                'class' => 'form-control input-sm',
                                'value' => $category_info->name)
                        ); ?>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('categories_lang.category_description'), 'description', array('class' => 'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <?php echo form_textarea(array(
                                'name' => 'description',
                                'id' => 'description',
                                'class' => 'form-control input-sm',
                                'value' => $category_info->description)
                        ); ?>
                    </div>
                </div>

                <!--    CUSTOM 2 (Item Type) -->
                <div class="form-group form-group-sm">
                    <?php echo form_label($appData['custom2_name'], 'custom2', array('class' => 'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <?php echo form_dropdown('custom2', [''=>'Choose','quantity'=>'Quantity','scale'=>'Scale', 'price'=>'Price'],
                                    isset($category_info->custom2) ? $category_info->custom2 : '', array('class' => 'form-control')
                                ); ?>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('raw_items_lang.raw_items_image'), 'raw_items_image', array('class' => 'control-label col-xs-3')); ?>
                    <div class='col-xs-8'>
                        <div class="fileinput <?php echo $logo_exists ? 'fileinput-exists' : 'fileinput-new'; ?>"
                             data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 100px; height: 100px;"></div>
                            <div class="fileinput-preview fileinput-exists thumbnail"
                                 style="max-width: 100px; max-height: 100px;">
                                <img data-src="holder.js/100%x100%" alt="<?php echo lang('raw_items_lang.raw_items_image'); ?>"
                                     src="<?php echo isset($image_path)? $image_path: ''; ?>"
                                     style="max-height: 100%; max-width: 100%;">
                            </div>
                            <div>
                                <span class="btn btn-default btn-sm btn-file">
                                    <span class="fileinput-new"><?php echo lang("raw_items_lang.raw_items_select_image"); ?></span>
                                    <span class="fileinput-exists"><?php echo lang("raw_items_lang.raw_items_change_image"); ?></span>
                                    <input type="file" name="item_image" accept="image/*">
                                </span>
                                <a href="#" class="btn btn-default btn-sm fileinput-exists"
                                   data-dismiss="fileinput"><?php echo lang("raw_items_lang.raw_items_remove_image"); ?></a>
                            </div>
                        </div>
                    </div>
                </div>

        	</fieldset>
        </div>
        <div class="tab-pane" id="items_attribute_info">
            <fieldset style="height: 400px;">

                <script type="text/javascript">
                    <?php if($category_info->item_id){ ?>
                        $('input[type=radio][name=is_pizza]').attr('disabled','disabled');
                    <?php } ?>
                </script>
                <div class="form-group form-group-sm">
                    <?php echo form_label(lang('raw_order_lang.raw_orders_category'), 'is_pizza', array('class'=>'required control-label col-xs-3')); ?>
                    <div class="col-xs-8">
                        <label class="radio-inline">
                            <?php echo form_radio(array(
                                    'name'=>'is_pizza',
                                    'type'=>'radio',
                                    'id'=>'is_pizza',
                                    'value'=>0,
                                    'checked'=>'checked')
                                    ); ?> <?php echo lang('categories_lang.categories_item_other'); ?>
                        </label>
                        <label class="radio-inline">
                            <?php echo form_radio(array(
                                    'name'=>'is_pizza',
                                    'type'=>'radio',
                                    'id'=>'is_pizza',
                                    'value'=>1,
                                    'checked'=>$category_info->is_pizza === '1')
                                    ); ?> <?php echo lang('categories_lang.categories_item_pizza'); ?>
                        </label>
                    </div>
                </div>
                

                <?php $display=''; if($category_info->is_pizza==0 || $category_info->is_pizza==''){ 
                    $display = 'style="display: none;"';
                }?>
                <div id="display_pizze_ingredients" <?php echo $display ?>>
                    <div id="required_fields_message"><?php echo lang('categories_lang.required_attributes_ingredients_message'); ?></div>

                    <?php if(!$category_info->item_id || count($suggest_attributes_ingredients)==0){ ?>
                        <div class="col-xs-12">
                            <?php echo form_label(lang('categories_lang.items_attribute_ingredients'), 'items_attribute_ingredients', array('class' => 'required control-label col-xs-3')); ?>

                            <div class="col-xs-8" style="margin-right: 5px;">
                                <div class="form-group form-group-sm">
                                <?php echo form_input(array(
                                        'name' => 'attribute_ingredient_titles[0]',
                                        'id' => 'attribute_ingredient_name_1',
                                        'class' => 'form-control input-sm',
                                        'placeholder' => 'Title',
                                        'value' => '')
                                ); ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }else{ 
                        foreach ($suggest_attributes_ingredients as $key => $attributes) { 
                    ?>
                        <div class="col-xs-12">
                            <?php 
                                // for first element display label and delete btn for all othrs
                                if($key==0){
                                    echo form_label(lang('categories_lang.items_attribute_ingredients'), 'items_attribute_ingredients', array('class' => 'control-label col-xs-3'));
                                }else{
                                    echo "<div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div>";
                                }
                            ?>
                                <div class="col-xs-8" style="margin-right: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_ingredient_titles['.$key.']',
                                            'id' => 'attribute_ingredient_name_1',
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Title',
                                            'value' => $attributes['attribute_title'])
                                    ); ?>
                                    </div>
                                </div>
                        </div>
                    <?php }} ?>

                    <div id="append_more_attributes3"></div>

                    <button class="btn btn-info btn-sm center-block" type="button" id="more_attributes3"><i class="glyphicon glyphicon-plus"></i> Add</button>
                </div>

                <br>

                <div id="required_fields_message"><?php echo lang('categories_lang.required_attributes_size_message'); ?></div>

                <div id="display_attribute">
                    <?php if($category_info->is_pizza==0 || $category_info->is_pizza==''){ ?>
                    <?php if(!$category_info->item_id || count($suggest_attributes)==0){ ?>
                        <div class="col-xs-12">
                            <?php echo form_label(lang('categories_lang.items_attribute_size'), 'items_attribute_size', array('class' => 'required control-label col-xs-3')); ?>

                            <div class="col-xs-4" style="margin-right: 5px;">
                                <div class="form-group form-group-sm">
                                <?php echo form_input(array(
                                        'name' => 'attribute_titles[0]',
                                        'id' => 'attribute_name_1',
                                        'class' => 'form-control input-sm',
                                        'placeholder' => 'Title',
                                        'value' => '')
                                ); ?>
                                </div>
                            </div>
                            <div class="col-xs-4" style="margin-left: 5px;">
                                <div class="form-group form-group-sm">
                                <?php echo form_input(array(
                                        'name' => 'attribute_prices[0]',
                                        'id' => 'attribute_price_1',
                                        'class' => 'form-control input-sm',
                                        'placeholder' => 'Price',
                                        'value' => '')
                                ); ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }else{ 
                        foreach ($suggest_attributes as $key => $attributes) { 
                    ?>
                        <div class="col-xs-12">
                            <?php 
                                // for first element display label and delete btn for all othrs
                                if($key==0){
                                    echo form_label(lang('categories_lang.items_attribute_size'), 'items_attribute_size', array('class' => 'required control-label col-xs-3'));
                                }else{
                                    echo "<div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div>";
                                }
                            ?>
                                <div class="col-xs-4" style="margin-right: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_titles['.$key.']',
                                            'id' => 'attribute_name_1',
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Title',
                                            'value' => $attributes['attribute_title'])
                                    ); ?>
                                    </div>
                                </div>
                                <div class="col-xs-4" style="margin-left: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_prices['.$key.']',
                                            'id' => 'attribute_price_1',
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Price',
                                            'value' => $attributes['attribute_price'])
                                    ); ?>
                                    </div>
                                </div>
                        </div>
                    <?php 
                        }}}else{

                        $siz_array = ['min','small','medium','large','xlarge'];
                        $siz_values = ['Mi','S','M','L','XL'];
                        $count = 1;
                        foreach ($suggest_attributes as $key => $attributes) { 
                    ?>
                        <div class="col-xs-12">
                            <?php 
                                echo form_label(lang('categories_lang.items_attribute_'.$siz_array[$key]), 'items_attribute_'.$siz_array[$key], array('class' => 'required control-label col-xs-3'));
                            ?>
                                <div class="col-xs-4" style="margin-right: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_titles['.$key.']',
                                            'id' => 'attribute_name_'.$count,
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Title',
                                            'readonly' => 'readonly',
                                            'value' => $siz_values[$key])
                                    ); ?>
                                    </div>
                                </div>
                                <div class="col-xs-4" style="margin-left: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_prices['.$key.']',
                                            'id' => 'attribute_price_'.$count,
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Price',
                                            'value' => $attributes['attribute_price'])
                                    ); ?>
                                    </div>
                                </div>
                        </div>
                    <?php $count++; }} ?>
                </div>
                <div id="display_add_more_sizes">
                    <?php if($category_info->is_pizza==0 || $category_info->is_pizza==''){ ?>
                    <div id="append_more_attributes"></div>

                    <button class="btn btn-info btn-sm center-block" type="button" id="more_attributes"><i class="glyphicon glyphicon-plus"></i> Add</button>
                    <?php } ?>
                </div>
                
                <br>

                <?php $display=''; if($category_info->is_pizza==0 || $category_info->is_pizza==''){ 
                    $display = 'style="display: none;"';
                }?>
                <div id="display_pizze_extras" <?php echo $display ?>>
                    <div id="required_fields_message"><?php echo lang('categories_lang.required_attributes_extras_message'); ?></div>

                    <?php if(!$category_info->item_id || count($suggest_attributes_extras)==0){ ?>
                        <div class="col-xs-12">
                            <?php echo form_label(lang('categories_lang.items_attribute_extra'), 'items_attribute_extra', array('class' => 'control-label col-xs-3')); ?>

                            <div class="col-xs-4" style="margin-right: 5px;">
                                <div class="form-group form-group-sm">
                                <?php echo form_input(array(
                                        'name' => 'attribute_extra_titles[0]',
                                        'id' => 'attribute_extra_name_1',
                                        'class' => 'form-control input-sm',
                                        'placeholder' => 'Title',
                                        'value' => '')
                                ); ?>
                                </div>
                            </div>
                            <div class="col-xs-4" style="margin-left: 5px;">
                                <div class="form-group form-group-sm">
                                <?php echo form_input(array(
                                        'name' => 'attribute_extra_prices[0]',
                                        'id' => 'attribute_extra_price_1',
                                        'class' => 'form-control input-sm',
                                        'placeholder' => 'Price',
                                        'value' => '')
                                ); ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }else{ 
                        foreach ($suggest_attributes_extras as $key => $attributes) { 
                    ?>
                        <div class="col-xs-12">
                            <?php 
                                // for first element display label and delete btn for all othrs
                                if($key==0){
                                    echo form_label(lang('categories_lang.items_attribute_extra'), 'items_attribute_extra', array('class' => 'control-label col-xs-3'));
                                }else{
                                    echo "<div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div>";
                                }
                            ?>
                                <div class="col-xs-4" style="margin-right: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_extra_titles['.$key.']',
                                            'id' => 'attribute_extra_name_1',
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Title',
                                            'value' => $attributes['attribute_title'])
                                    ); ?>
                                    </div>
                                </div>
                                <div class="col-xs-4" style="margin-left: 5px;">
                                    <div class="form-group form-group-sm">
                                    <?php echo form_input(array(
                                            'name' => 'attribute_extra_prices['.$key.']',
                                            'id' => 'attribute_extra_price_1',
                                            'class' => 'form-control input-sm',
                                            'placeholder' => 'Price',
                                            'value' => $attributes['attribute_price'])
                                    ); ?>
                                    </div>
                                </div>
                        </div>
                    <?php }} ?>

                    <div id="append_more_attributes2"></div>

                    <button class="btn btn-info btn-sm center-block" type="button" id="more_attributes2"><i class="glyphicon glyphicon-plus"></i> Add</button>
                </div>
            </fieldset>
        </div>
    </div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{    
    $('input:radio[name="is_pizza"]').change(function(){
        if($(this).val()==0){
            $('#display_attribute').html('');
            $('#append_more_attributes').html('');
            $('#display_pizze_extras').css('display','none');
            $('#display_pizze_ingredients').css('display','none');
            //$('#error_message_box').html('');

            

            $('#display_attribute').append("<div class='col-xs-12'><label for='items_attribute_size' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_size') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[0]' class='form-control input-sm' placeholder='Title' id= 'attribute_name_1'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[0]' class='form-control input-sm' placeholder='Price' id='attribute_price_1'></div></div></div>").hide().fadeIn();
            
            $('#display_add_more_sizes').fadeIn();
            

            $('#attribute_ingredient_name_1').rules("remove", "required");
            // $('#attribute_ingredient_price_1').rules("remove", "required");
            // $('#attribute_ingredient_price_1').rules("remove", "number");

            $("#attribute_name_1").rules('add', {
                required: true,
                messages: {
                    required: "Size 1 title is a required field",
                }
            });

            $("#attribute_price_1").rules('add', {
                required: true,
                number: true,
                messages: {
                    required: "Size 1 price is a required field",
                    number: "Size 1 price in not a valid Number",
                }
            });


            var j;
            for (j = 2; j <= 5; j++) { 
                $("#attribute_price_"+j).rules("remove", "required");
                $("#attribute_price_"+j).rules("remove", "number");
            }
            
        }else{
            $('#display_attribute').html('');
            $('#append_more_attributes').html('');
            $('#display_add_more_sizes').hide();
            //$('#error_message_box').html('');
            
            $('#display_attribute').append("<div class='col-xs-12'><label for='items_attribute_min' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_min') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[0]' class='form-control input-sm' placeholder='Title' value='Mi' readonly='readonly' id= 'attribute_name_1'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[0]' class='form-control input-sm' placeholder='Price' id='attribute_price_1'></div></div></div><div class='col-xs-12'><label for='items_attribute_small' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_small') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[1]' class='form-control input-sm' placeholder='Title' value='S' readonly='readonly' id= 'attribute_name_2'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[1]' class='form-control input-sm' placeholder='Price' id='attribute_price_2'></div></div></div><div class='col-xs-12'><label for='items_attribute_medium' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_medium') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[2]' class='form-control input-sm' placeholder='Title' value='M' readonly='readonly' id= 'attribute_name_3'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[2]' class='form-control input-sm' placeholder='Price' id='attribute_price_3'></div></div></div><div class='col-xs-12'><label for='items_attribute_large' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_large') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[3]' class='form-control input-sm' placeholder='Title' value='L' readonly='readonly' id= 'attribute_name_4'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[3]' class='form-control input-sm' placeholder='Price' id='attribute_price_4'></div></div></div><div class='col-xs-12'><label for='items_attribute_xlarge' class='required control-label col-xs-3'><?php echo lang('categories_lang.items_attribute_xlarge') ?></label><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles[4]' class='form-control input-sm' placeholder='Title' value='XL' readonly='readonly' id= 'attribute_name_5'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices[4]' class='form-control input-sm' placeholder='Price' id='attribute_price_5'></div></div></div>").hide().fadeIn();

            $('#attribute_name_1').rules("remove", "required");
            $('#attribute_price_1').rules("remove", "required");

            var i;
            var siz_array = ['','Mini','Small','Medium','Large','Xlarge'];
            for (i = 1; i <= 5; i++) { 
                $("#attribute_price_"+i).rules('add', {
                    required: true,
                    number: true,
                    messages: {
                        required: siz_array[i]+" price is a required field",
                        number: siz_array[i]+" price in not a valid Number",
                    }
                });
            }

            $("#attribute_ingredient_name_1").rules('add', {
                required: true,
                messages: {
                    required: "Ingredient 1 title is a required field",
                }
            });

            // $("#attribute_ingredient_price_1").rules('add', {
            //     required: true,
            //     number: true,
            //     messages: {
            //         required: "Ingredient 1 price is a required field",
            //         number: "Ingredient 1 price in not a valid Number",
            //     }
            // });

            $('#display_pizze_extras').fadeIn();
            $('#display_pizze_ingredients').fadeIn();

        }
    });
    
    var glob = '<?php echo (count($suggest_attributes)==0) ? '1' : count($suggest_attributes) ?>';
    var globalindex = parseInt(glob)+1;

    $('#more_attributes').on('click', function(e) {
        $('#append_more_attributes').append("<div class='col-xs-12'><div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_titles["+glob+"]' class='form-control input-sm' placeholder='Title' id= 'attribute_name_"+globalindex+"'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_prices["+glob+"]' class='form-control input-sm' placeholder='Price' id='attribute_price_"+globalindex+"'></div></div></div>");

            $("#attribute_name_"+globalindex).rules('add', {
                required: true,
                messages: {
                    required: "Size "+globalindex+" title is a required field",
                }
            });

            $("#attribute_price_"+globalindex).rules('add', {
                required: true,
                number: true,
                messages: {
                    required: "Size "+globalindex+" price is a required field",
                    number: "Size "+globalindex+" price in not a valid Number",
                }
            });

            globalindex++;
            glob++;
    });

    var glob2 = '<?php echo (count($suggest_attributes_extras)==0) ? '1' : count($suggest_attributes_extras) ?>';
    var globalindex2 = parseInt(glob2)+1;

    $('#more_attributes2').on('click', function(e) {
        $('#append_more_attributes2').append("<div class='col-xs-12'><div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_extra_titles["+glob2+"]' class='form-control input-sm' placeholder='Title' id= 'attribute_extra_name_"+globalindex2+"'></div></div><div class='col-xs-4' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_extra_prices["+glob2+"]' class='form-control input-sm' placeholder='Price' id='attribute_extra_price_"+globalindex2+"'></div></div></div>");

            globalindex2++;
            glob2++;
    });

    var glob3 = '<?php echo (count($suggest_attributes_ingredients)==0) ? '1' : count($suggest_attributes_ingredients) ?>';
    var globalindex3 = parseInt(glob3)+1;

    $('#more_attributes3').on('click', function(e) {
        $('#append_more_attributes3').append("<div class='col-xs-12'><div class='col-xs-3'><a href='#' class='btn btn-danger btn-xs pull-right' onclick='return delete_raw_order_row(this);'><span class='glyphicon glyphicon-trash'></span></a></div><div class='col-xs-8' style='margin-right: 10px;'><div class='form-group form-group-sm'><input type='text' name='attribute_ingredient_titles["+glob3+"]' class='form-control input-sm' placeholder='Title' id= 'attribute_ingredient_name_"+globalindex3+"'></div></div></div>");

            $("#attribute_ingredient_name_"+globalindex3).rules('add', {
                required: true,
                messages: {
                    required: "Ingredient "+globalindex3+" title is a required field",
                }
            });

            // $("#attribute_ingredient_price_"+globalindex3).rules('add', {
            //     required: true,
            //     number: true,
            //     messages: {
            //         required: "Ingredient "+globalindex3+" price is a required field",
            //         number: "Ingredient "+globalindex3+" price in not a valid Number",
            //     }
            // });

            globalindex3++;
            glob3++;
    });
    
    // $('.bootstrap-tagsinput').addClass('form-control');

    // $(".bootstrap-tagsinput input").focus(function(){
    //    $(this).parent().addClass("blackbg");
    // }).blur(function(){
    //     $(this).parent().removeClass("blackbg");
    // });

    // $('#category_form').on('keyup keypress', function(e) {
    //     var keyCode = e.keyCode || e.which;
    //     if (keyCode === 13) { 
    //         e.preventDefault();
    //         return false;
    //     }
    // });

    $("#category").autocomplete({
        source: "<?php echo base_url('categories/suggest_category');?>",
        delay: 10,
        appendTo: '.modal-content'
    });

    $.validator.setDefaults({ignore: []});

    $('#category_form').validate($.extend({
        submitHandler:function(form)
        {   

            $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
            $('#submit').hide();
	        $(form).ajaxSubmit({
	            success:function(response)
	            {
	                dialog_support.hide();
	                table_support.handle_submit('<?php echo base_url($controller_name); ?>', response);

                    // table_support.refresh();
	            },
	            dataType:'json'
	        });

        },
        rules:
        {
            category: "required",
            category_name: "required",
            item_number: {
                required: false,
                remote: {
                    url: "<?php echo base_url($controller_name . '/check_item_number')?>",
                    type: "post",
                    data: $.extend(csrf_form_base(),
                        {
                            "item_id": "<?php echo $category_info->item_id; ?>",
                            "item_number": function () {
                                return $("#item_number").val();
                            }
                        })
                },
            },
            <?php if(!$category_info->item_id || count($suggest_attributes)==0){ ?>
                'attribute_titles[0]': "required",
                'attribute_prices[0]': {
                    required: true,
                    number: true,
                },
            <?php }else{ 
                for ($i=0; $i < count($suggest_attributes); $i++) { ?>
                'attribute_titles[<?php echo $i ?>]': "required",
                'attribute_prices[<?php echo $i ?>]': {
                    required: true,
                    number: true,
                },
            <?php }} ?>
            <?php if($category_info->item_id || count($suggest_attributes_extras)>0){
                for ($i=0; $i < count($suggest_attributes_extras); $i++) { ?>
                'attribute_extra_titles[<?php echo $i ?>]': "required",
                'attribute_extra_prices[<?php echo $i ?>]': {
                    required: true,
                    number: true,
                },
            <?php }}else{ ?>
                'attribute_extra_prices[0]': {
                    // required: true,
                    number: true,
                },
            <?php } ?>
            <?php if(!$category_info->item_id || count($suggest_attributes_ingredients)==0){ ?>
                // 'attribute_ingredient_titles[0]': "required",
                // 'attribute_ingredient_prices[0]': {
                //     required: true,
                //     number: true,
                // },
            <?php }else{
                for ($i=0; $i < count($suggest_attributes_ingredients); $i++) { ?>
                'attribute_ingredient_titles[<?php echo $i ?>]': "required",
                // 'attribute_ingredient_prices[<?php echo $i ?>]': {
                //     required: true,
                //     number: true,
                // },
            <?php }} ?>
        },
        messages: 
        {
            category: "<?php echo lang('categories_lang.categories_title_required'); ?>",
            category_name: "<?php echo lang('categories_lang.categories_name_title_required'); ?>",
            item_number:"<?php echo lang('items_lang.items_item_number_duplicate'); ?>",
            <?php if(!$category_info->item_id || count($suggest_attributes)==0){ ?>
                'attribute_titles[0]': "Size 1 title is a required field",
                'attribute_prices[0]': {
                    required: "Size 1 price is a required field",
                    number: "Size 1 price in not a valid Number",
                },
            <?php }else{
                for ($i=0; $i < count($suggest_attributes); $i++) { ?>
                'attribute_titles[<?php echo $i ?>]': "Size <?php echo $i+1 ?> title is a required field",
                'attribute_prices[<?php echo $i ?>]': {
                    required: "Size <?php echo $i+1 ?> price is a required field",
                    number: "Size <?php echo $i+1 ?> price in not a valid Number",
                },
            <?php }} ?>
            <?php if($category_info->item_id || count($suggest_attributes_extras)>0){
                for ($i=0; $i < count($suggest_attributes_extras); $i++) { ?>
                'attribute_extra_titles[<?php echo $i ?>]': "Topping <?php echo $i+1 ?> title is a required field",
                'attribute_extra_prices[<?php echo $i ?>]': {
                    required: "Topping <?php echo $i+1 ?> price is a required field",
                    number: "Topping <?php echo $i+1 ?> price in not a valid Number",
                },
            <?php }}else{ ?>
                'attribute_extra_prices[0]': {
                    number: "Topping 1 price in not a valid Number",
                },
            <?php } ?>
            <?php if(!$category_info->item_id || count($suggest_attributes_ingredients)==0){ ?>
                // 'attribute_ingredient_titles[0]': "Ingredient 1 title is a required field",
                // 'attribute_ingredient_prices[0]': {
                //     required: "Ingredient 1 price is a required field",
                //     number: "Ingredient 1 price in not a valid Number",
                // },
            <?php }else{
                for ($i=0; $i < count($suggest_attributes_ingredients); $i++) { ?>
                'attribute_ingredient_titles[<?php echo $i ?>]': "Ingredient <?php echo $i+1 ?> title is a required field",
                // 'attribute_ingredient_prices[<?php echo $i ?>]': {
                //     required: "Ingredient <?php echo $i+1 ?> price is a required field",
                //     number: "Ingredient <?php echo $i+1 ?> price in not a valid Number",
                // },
            <?php }} ?>

        }
    }, form_support.error));

});

function delete_raw_order_row(link)
{
    $(link).parent().parent().remove();
    return false;
}

</script>