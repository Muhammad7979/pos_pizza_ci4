<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" href="<?php echo base_url('css/pizza_style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('css/pizza_responsive.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('css/jquery.datetimepicker.min.css'); ?>">

    <script src="<?php echo base_url('js/jquery.js'); ?>"></script>
    <script src="<?php echo base_url('js/jQuery.print.js'); ?>"></script>
    <script src="<?php echo base_url('js/jquery.datetimepicker.full.min.js'); ?>"></script>
    <title>Pizza Menu</title>
</head>

<body>
    <style>
        @media print {
            #fullScreen, #mydiv {
              display:none;
            }
        }
        #mydiv {
          position: absolute;
          z-index: 9;
          text-align: center;
        }

        #mydivheader {
          padding: 10px;
          cursor: pointer;
          z-index: 10;
          color: #fff;
        }
        .pizza_menu_block .menu_categories ul li span{ width: 122px;display: inline-block;}
        .menu_items .price {padding-right: 24px}
        .pizza_menu_block .logo{padding:15px 15px; box-sizing: border-box;}

    </style>
    <!-- <div id="mydiv">
      <div id="mydivheader" onclick="openFullscreen()">
        <img src="<?php //echo base_url('images/expand-arrows.png'); ?>" alt="">
      </div>
    </div> -->
    
    <div class="tehzeeb_pizza_menu" id="fullScreen">
        <div id="overlay">
            <div class="cv-spinner">
                <span class="spinner"></span>
            </div>
        </div>
        <div class="pizza_menu_block">
            <div class="inner_container inner_container_width">
                <div class="logo">
                    <img src="<?php echo base_url('images/tehzeeb_logo.png'); ?>" alt="">
                        
                        <div class="dropdown" style="float: right; padding: 20px 13px 0px 0px">
                            <a href="javascript:" class="checkoutModalBtn" style="float: left" aria-expanded="false">
                                <img src="<?php echo base_url('images/cart.png'); ?>" style="max-width: 100%; height: 40px" alt="">
                            </a>
                            <span class="notification_icon" id="noti_count">0</span>
                        </div>

                </div>

                <div class="menu_categories">
                    <ul>
                        <li><span>Flavors</span></li>
                        <li><span>Size</span></li>
                        <li><span>Price</span></li>
                        <li><span>Quantity</span></li>
                        <li></li>
                    </ul>
                </div>

                <div class="menu_items">
                    <ul>
                        <?php foreach ($pizza_items->getResult() as $key => $items): ?>

                        <?php 
                            $image = '';
                            if (!empty($items->pic_id)) {
                                $images = glob("uploads/item_pics/" . $items->pic_id . ".*");
                                if (sizeof($images) > 0) {
                                    $image = base_url($images[0]);
                                }
                            }
                        ?>
                        <li>
                            <form id="form<?php echo $items->item_id ?>" action="" method="POST">
                               
                                <div class="flavors">
                                    <img src="<?php echo $image ?>" alt="" id="item_image<?php echo $items->item_id ?>">
                                    <div class="pizza_description">
                                        <span><?php echo $items->name ?></span>
                                        <p>
                                            <?php echo $items->ingredients ?>
                                        </p>
                                    </div>
                                    <input type="hidden" name="item_id" value="<?php echo $items->item_id ?>" id="item_id"/>
                                    <input type="hidden" name="type" value="<?php echo $items->item_type ?>" id="type"/>
                                    <input type="hidden" name="layer" value="0">
                                    <input type="hidden" name="item_name" value="<?php echo $items->name ?>">
                                    <input type="hidden" name="ingredients1_title" value="<?php //echo $items->ingredients ?>">
                                    <input type="hidden" name="ingredients1" value="<?php //echo $items->ingredient_ids ?>">
                                    <input type="hidden" name="item_description" value="">
                                    <input type="hidden" name="item_category1" value="<?php echo $items->category ?>">
                                </div>


                                <div class="size noselect">
                                    <div class="toggle">
                                    <?php foreach ($items->sizes as $key1 => $size): ?>
                                        <input type="radio" class="sizeRadio<?php echo $items->item_id ?>" name="size" value="<?php echo $size->attribute_id ?>" data-id="<?php echo $items->item_id ?>" data-price="<?php echo parse_decimals($size->attribute_price) ?>" id="size<?php echo $items->item_id ?><?php echo $size->attribute_id ?>" <?php echo ($key1==0) ? 'checked="checked"' : ''; ?>/>
                                        <label for="size<?php echo $items->item_id ?><?php echo $size->attribute_id ?>"><?php echo $size->attribute_title ?></label>
                                    <?php endforeach ?>
                                    </div>
                                </div> 

                                <div class="price">
                                    <span id="price_text<?php echo $items->item_id ?>"><?php echo parse_decimals($items->sizes[0]->attribute_price) ?>/-</span>
                                    <input type="hidden" name="price" value="<?php echo parse_decimals($items->sizes[0]->attribute_price) ?>" id="price<?php echo $items->item_id ?>"/>
                                    <input type="hidden" name="price_single" value="<?php echo parse_decimals($items->sizes[0]->attribute_price) ?>" id="price_single<?php echo $items->item_id ?>"/>
                                </div>  

                                <div class="quantity noselect">
                                    <div class="number">
                                        <span class="minus">-</span>
                                        <input class="quan" type="text" readonly="readonly" data-id="<?php echo $items->item_id ?>" name="quantity" value="1" id="quantity<?php echo $items->item_id ?>"/>
                                        <span class="plus">+</span>
                                    </div>
                                </div>  


                                <div class="order_customize">
                                    <!-- <button type="button" class="button" value="<?php //echo $items->item_id ?>">Order now</button> -->
                                    <button type="button" class="cartButton" style="margin-bottom: 10px;" value="<?php echo $items->item_id ?>">Add To Cart</button>
                                    <button type="button" class="modalButton" value="<?php echo $items->item_id ?>">Customize</button>
                                </div>      

                            </form>     
                        </li>

                        <?php endforeach ?>

                    </ul>
                </div>
            </div>


        </div>
    


        <div id="myModal" class="modal">

            <!-- Modal content -->
            <div class="modal-content">
                <span class="close">
                    <img src="<?php echo base_url('images/close-Icon.png'); ?>" alt="">
                </span>
                <form id="customForm" action="" method="POST">
                <div class="pizza_popup_header">
                    <div class="image">
                        <img src="" alt="" id="custom_item_image"> 
                    </div>
                    <div class="pizza_name">
                        <span id="item_name"></span>
                        <span id="item_plus"></span>
                        <span id="other_item_name"></span>
                        <p id="item_description"></p>
                    </div>
                </div>

                <div class="alert" id="alert" style="display: none;">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                    <img src="<?php echo base_url('images/alert_icon.png'); ?>" alt=""> 
                    <span class="alert_text"> Please Select Other Flavor</span>
                </div>

                <div class="alert" id="alert_other" style="display: none;">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                    <img src="<?php echo base_url('images/alert_icon.png'); ?>" alt=""> 
                    <span class="alert_text"> Can't Add Another Flavor For Mini Pizza</span>
                </div>

                <div class="add_more_block noselect">
                    <div class="left">

                        <div class="half_and_half">
                            <span>Crust</span>
                            <div class="toggle toggle_two">
                                <input type="radio" name="layer" value="0" id="crust0" checked="checked" />
                                <label for="crust0" class="crust_label">Thick</label>
                                <input type="radio" name="layer" value="1" id="crust1" />
                                <label for="crust1" class="crust_label">Thin</label>
                            </div>
                        </div>

                        <div class="half_and_half">
                            <span>Dough</span>
                            <div class="toggle toggle_two">
                                <input type="radio" name="dough" value="0" id="dough0" checked="checked" />
                                <label for="dough0" class="crust_label">Plain</label>
                                <input type="radio" name="dough" value="1" id="dough1" />
                                <label for="dough1" class="crust_label">W WH</label>
                            </div>
                        </div>

                        <div class="half_and_half">
                            <span>Is Half N Half</span>
                            <div class="toggle toggle_two">
                                <input type="radio" name="is_half" value="1" id="is_halfyes"/>
                                <label for="is_halfyes" class="crust_label">Yes</label>
                                <input type="radio" name="is_half" value="0" id="is_halfno" checked="checked"/>
                                <label for="is_halfno" class="crust_label">No</label>
                            </div>
                        </div>

                        <div class="pop_up_add_flavors">
                            <span>Flavor 2</span>
                            <label id="select_label">
                                <select id="other_items" name="add_item_id" disabled="disabled">
                                    <option value="">Select</option>
                                </select> 
                            </label>
                        </div>  


                    </div>

                    <div class="right">

                        <div class="topping_block">

                            <div class="extra_topping">
                                <span>Remove Ingredients</span>

                                <ul id="extras3">
                                    

                                </ul>

                            </div>

                            <div class="extra_topping">
                                <span>Add Toppings</span>

                                <ul id="extras1">
                                    

                                </ul>

                            </div>

                            <div class="extra_topping">
                                <span style="display: none;" id="ingredients2_heading">Remove Ingredients Flavor 2</span>

                                <ul id="extras4">
                                    

                                </ul>

                            </div>

                            <div class="extra_topping extra_topping_two">
                                <span style="display: none;" id="extras2_heading">Add Toppings Flavor 2</span>

                                <ul id="extras2">
                                    

                                </ul>

                            </div>
                        </div>

                        <div class="pop_up_textarea">
                            <textarea class="add_ins" rows="6" name="item_description"  placeholder="Additional Instructions...."></textarea>
                        </div>  

                        <div class="pop_up_total_price">
                            <!-- <button type="button" class="customButton">Order Now</button> -->
                            <button type="button" class="customCartButton" value="<?php echo $items->item_id ?>">Add To Cart</button>
                            <span id="custom_price_text">Pkr 0/-</span>
                            <input type="hidden" name="price" value="0" id="custom_price">
                            <input type="hidden" name="price_single" value="0" id="custom_price_single">
                            <input type="hidden" name="price_single_wf2" value="0" id="custom_price_single_wf2">
                            <input type="hidden" name="extras_price" value="0" id="custom_extras_price">
                        </div>  

                    </div>

                </div>

                <input type="hidden" name="item_id" value="0" id="custom_item_id"/>
                <input type="hidden" name="item_category1" value="" id="custom_item_category1"/>
                <input type="hidden" name="item_category2" value="" id="custom_item_category2"/>
                <input type="hidden" name="item_name" value="" id="custom_item_name"/>
                <input type="hidden" name="add_item_name" value="" id="custom_add_item_name"/>
                <input type="hidden" name="type" value="0" id="custom_type"/>
                <input type="hidden" name="quantity" value="0" id="custom_quantity"/>
                <input type="hidden" name="size" value="0" id="custom_size">
                <input type="hidden" name="extras1_title" value="" id="custom_extras1">
                <input type="hidden" name="extras2_title" value="" id="custom_extras2">
                <input type="hidden" name="ingredients1_title" value="" id="custom_ingredients1">
                <input type="hidden" name="ingredients2_title" value="" id="custom_ingredients2">

                </form>

            </div>

        </div>

        <div id="checkoutModal" class="modal" style="color: #fff">

            <!-- Modal content -->
            <div class="modal-content">
                <span class="checkoutClose">
                    <img src="<?php echo base_url('images/close-Icon.png'); ?>" alt="" >
                </span>
                
                
                <div class="logo" style="text-align: center;">
                    <img src="<?php echo base_url('images/checkout.png'); ?>" alt="">
                </div>

                <div class="checkout_table">
            
                    <table>
                        <thead>
                        <tr class="header">
                            <th>Name</th>
                            <th>Size</th>
                            <th>C / D</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Sub Total</th>
                            <th></th>
                            
                        </tr>
                        </thead>
                        <tbody id="detail_cart">

                            <tr class="showhr">
                            <!--     <td>Grilled Chicken Pizza</td>
                                <td>Mini</td>
                                <td>Thick / Plain</td>
                                <td>700</td>
                                <td>1</td>
                                <td>700</td>
                                <td><button type="button" id="f2eb4d24721fac863c18c017b9ae0b97" class="remove_cart">Remove</button></td>
                            </tr>
                            <tr class="hidehr" style="display: none;">
                                <td colspan="7">
                                    All Details Goes Here<br>
                                    All Details Goes Here<br>
                                    All Details Goes Here<br>
                                    All Details Goes Here<br>
                                    All Details Goes Here<br>
                                </td>-->
                            </tr>

                        </tbody>
                    </table>

                    <div class="cart_more_block">
        
                        <div class="full">

                            <div class="pop_up_input_parent">
                                <div class="pop_up_input">
                                    <!-- <label for="name">Name:</label> -->
                                    <input autocomplete="off" type="text" id="customer_name" placeholder="Enter Name" name="customer_name">
                                </div> 

                                <div class="pop_up_input">
                                    <!-- <label for="phone">Phone:</label> -->
                                    <input autocomplete="off" type="text" id="customer_phone" placeholder="Enter Phone" name="customer_phone">
                                </div> 
                            </div>

                            <div class="pop_up_textarea">
                                <div class="pop_up_input">
                                    <input type="hidden" name="order_description" value="" placeholder="Schedule Order">
                                    <input type="text" name="deliver_at" id="default_datetimepicker"/>
                                </div> 
                                <!-- <textarea rows="4" name="order_description" id="order_description" placeholder="Additional Instructions...."></textarea> -->
                            </div>  

                            <div class="pop_up_total_price">
                                <button type="button" class="orderButton">Order Now</button>
                                <span id="cart_total_price">PKR 0/-</span>
                            </div>  

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div id="successModal" class="modal" style="color: #fff">

            <!-- Modal content -->
            <div class="modal-content">
                <span id="successModalClose" class="checkoutClose">
                    <img src="<?php echo base_url('images/close-Icon.png'); ?>" alt="" >
                </span>
                
                
                <div class="logo" style="text-align: center;">
                    <img src="<?php echo base_url('images/successfull.png'); ?>" alt="">
                </div>

                <div class="checkout_table">

                    <div class="cart_more_block">
        
                        <div class="full" style="display: flex; justify-content: space-evenly;">

                            <!-- <div class="pop_up_input_parent">
                                <div class="pop_up_input"> -->
                                    <!-- <label for="name">Name:</label> -->
                                    <!-- <input autocomplete="off" type="text" id="customer_name" placeholder="Enter Name" name="customer_name">
                                </div> 

                                <div class="pop_up_input"> -->
                                    <!-- <label for="phone">Phone:</label> -->
                                    <!-- <input autocomplete="off" type="text" id="customer_phone" placeholder="Enter Phone" name="customer_phone">
                                </div> 
                            </div> -->

                            <!-- <div class="pop_up_textarea">
                                <div class="pop_up_input">
                                    <input type="hidden" name="order_description" value="" placeholder="Schedule Order">
                                    <input type="text" name="deliver_at" id="default_datetimepicker"/>
                                </div>  -->
                                <!-- <textarea rows="4" name="order_description" id="order_description" placeholder="Additional Instructions...."></textarea> -->
                            <!-- </div>   -->

                            <!-- <div class="pop_up_total_price"> -->
                                <button type="button" class="openCart" style="font-family: 'Sans Culottes W01 Regular';
                                                             padding: 5px 30px;
                                                             background-color: #fea741;
                                                             color: #000;
                                                             font-size: 24px;
                                                             border-radius: 50px;
                                                             border: 0;
                                                             font-weight: 600;
                                                             margin-bottom: 20px;" class="">Go to cart</button>

                                <button type="button" class="addMore" style="font-family: 'Sans Culottes W01 Regular';
                                                             padding: 5px 30px;
                                                             background-color: #fea741;
                                                             color: #000;
                                                             font-size: 24px;
                                                             border-radius: 50px;
                                                             border: 0;
                                                             font-weight: 600;
                                                             margin-bottom: 20px;" class="">Add more</button>
                            <!-- </div>   -->

                        </div>

                    </div>

                </div>

            </div>

        </div>
        
    </div>

    <div id="printJS_get_html"></div>

<!--     <script
    src="https://code.jquery.com/jquery-3.4.1.min.js"
    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
    crossorigin="anonymous"></script> -->

    <!-- <script src="<?php //echo base_url('js/jquery.js'); ?>"></script> -->
        
    <script type="text/javascript">
        var elem = document.getElementById("fullScreen");
        function openFullscreen() {
          if (elem.requestFullscreen) {
            elem.requestFullscreen();
          } else if (elem.mozRequestFullScreen) { /* Firefox */
            elem.mozRequestFullScreen();
          } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
            elem.webkitRequestFullscreen();
          } else if (elem.msRequestFullscreen) { /* IE/Edge */
            elem.msRequestFullscreen();
          }
        }
    </script>

    <script type="text/javascript">


        $( ".modalButton" ).click(function() {
            $('#alert_other').hide();
            var id = $(this).val();
            //var formData = $("#form"+$(this).val()).serializeArray();
            //alert(formData)
            var newSrc = $('#item_image'+id).attr('src');
            $('#custom_item_image').attr('src', newSrc);
            var values = {};
            $.each($("#form"+$(this).val()).serializeArray(), function (i, field) {
                values[field.name] = field.value;
            });

            $.ajax({
                method: "POST",
                url: "<?php echo site_url('pizza_orders/get_extras/') ?>"+values['item_id']+'/'+values['size'],
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);　
                },
                success: function(data)
                {   

                    $('#item_name').text(values['item_name']);
                    // $('#other_item_name').text(values['item_name']);
                    //$('#item_description').text(values['item_description']);
                    
                    data = $.parseJSON(data);
                    var checkboxes = $('#extras1');
                    checkboxes.empty();
                    $.each(data.extras, function(i, item) {
                        checkboxes.append('<li><input type="checkbox" class="extrasCheckbox" data-price="'+item['price']+'" data-title="'+item['label']+'" value="'+item['value']+'" id="extras1'+item['value']+'" name="extras1[]"><label for="extras1'+item['value']+'">'+item['label']+'</label><div class="check"></div></li>')
                    });

                    var checkboxes1 = $('#extras3');
                    checkboxes1.empty();
                    // var ingredients1_label = '';
                    $.each(data.ingredients, function(i, item) {
                        checkboxes1.append('<li><input type="checkbox" class="ingredientsCheckbox" data-price="'+item['price']+'" data-title="'+item['label']+'" value="'+item['value']+'" id="extras3'+item['value']+'" name="extras3[]"><label for="extras3'+item['value']+'">'+item['label']+'</label><div class="check"></div></li>');

                        //ingredients1_label += item['label']+', ';

                    });
                    // ingredients1_label = ingredients1_label.replace(/,\s*$/, "");
                    // $('#custom_ingredients1').val(ingredients1_label);
                    
                    // alert(JSON.stringify(data.items));
                    var dropdown = $('#other_items');
                    dropdown.empty();
                    dropdown.append($('<option></option>').val('').html('Select').attr("data-price",0).attr("data-category",''));
                    $.each(data.items, function (i, item) {
                        dropdown.append(
                            $('<option></option>').val(item['item_id']).html(item['name']).attr("data-price",item['price']).attr("data-category",item['category'])
                        );
                    });

                    EnableCheckBoxClickFunction();

                },
                complete: function() {
                    $("#overlay").fadeOut(300);
                },
            });

            $('#custom_item_id').val(values['item_id']);
            $('#custom_item_category1').val(values['item_category1']);
            $('#custom_item_name').val(values['item_name']);
            $('#custom_type').val(values['type']);
            $('#custom_quantity').val(values['quantity']);
            $('#custom_size').val(values['size']);
            $('#custom_price').val(values['price']);
            $('#custom_price_single').val(values['price']/values['quantity']);
            $('#custom_price_text').text('Pkr '+values['price']+'/-');

            $("#myModal").css('display','block');

        });

        $( ".close" ).click(function() {

            closeModal();

        });

        $( ".checkoutModalBtn" ).click(function() {
            $("#checkoutModal").css('display','block');
        });

        $( ".checkoutClose" ).click(function() {

            $("#checkoutModal").css('display','none');

        });


         $( ".addMore , #successModalClose" ).click(function() {

            $("#successModal").css('display','none');

          });

          $( ".openCart" ).click(function() {

           $("#successModal").css('display','none');
           $("#checkoutModal").css('display','block');

          });

        
        function closeModal(){
            // Radio change to No for crust
            // $("input[type=radio][name=is_half]").prop("checked", true);
            $("input[type=radio][name=is_half]")[0].checked = false;
            $("input[type=radio][name=is_half]")[1].checked = true;

            // Dropdown change to disabled and empty for flavors
            var dropdown = $('#other_items');
                dropdown.empty();
                dropdown.append($('<option></option>').val('').html('Select'));
                dropdown.attr('disabled','disabled');

            // Extras list empty for Topping
            var checkboxes = $('#extras1');
                    checkboxes.empty();
            var checkboxes = $('#extras2');
                    checkboxes.empty();

            var checkboxes = $('#extras3');
                    checkboxes.empty();
            var checkboxes = $('#extras4');
                    checkboxes.empty();

            $('#extras2_heading').hide();
            $('#ingredients2_heading').hide();

            $("#myModal").css('display','none');
        }


        $( ".customButton" ).click(function() {
            // alert($("#customForm").serialize());

            var check = $("input[name=is_half]:checked").val();
            var other_items = $("#other_items").val();

            if(check==0 || (check==1 && other_items!='') ){
                $('#alert').fadeOut();
                $.ajax({
                    method: "POST",
                    data: $("#customForm").serialize(),
                    url: "<?php echo site_url('pizza_orders/save')?>",
                    beforeSend: function() {
                        // setting a loader
                        $("#overlay").fadeIn(300);　
                    },
                    success: function(data)
                    {   
                        closeModal();
                        //alert(data);
                        var obj = JSON.parse(data);
                        if(obj.success==1){

                            var url = '<?php echo site_url('api/v1/user/generatePdf/')?>'+obj.id+'/'+obj.store+'/'+obj.counter;
                            window.open(url);
                            location.reload();
                        }
                    },
                    complete: function() {
                        $("#overlay").fadeOut(300);
                    },
                });
            }else{
                $('#alert').fadeIn();
            }
        });
        
        $( ".button" ).click(function() {
            //alert($("#form"+$(this).val()).serialize());
            $.ajax({
                method: "POST",
                data: $("#form"+$(this).val()).serialize(),
                url: "<?php echo site_url('pizza_orders/save')?>",
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);　
                },
                success: function(data)
                {   
                    //alert(data);
                    var obj = JSON.parse(data);
                    if(obj.success==1){
                        var url = '<?php echo site_url('api/v1/user/generatePdf/')?>'+obj.id+'/'+obj.store+'/'+obj.counter;
                        window.open(url);
                        location.reload();
                    }
                },
                complete: function() {
                    $("#overlay").fadeOut(300);
                },
            });
        });

        $( ".orderButton" ).click(function() {

            var customer_name = $('#customer_name').val();
            var customer_phone = $('#customer_phone').val();
            var deliver_at = $('#default_datetimepicker').val();
            var order_description = $('#order_description').val();

            $.ajax({
                method: "POST",
                data: {deliver_at: deliver_at, customer_name: customer_name, customer_phone: customer_phone, order_description: order_description},
                url: "<?php echo site_url('pizza_orders/save')?>",
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);　
                },
                success: function(data)
                {   
                    //alert(data);
                    var obj = JSON.parse(data);
                    if(obj.success==1){
                        $('#printJS_get_html').html(obj.html);
                        // $.print("#printJS_get_html");
                        $("#checkoutModal").css('display','none');
                        $("#detail_cart").html('');
                        $("#cart_total_price").html('PKR 0/-');
                        $("#noti_count").html('0');
                        // $(".quan").val('1');
                        $(".orderButton").prop("disabled", true);
                        $.print("#printJS_get_html");
                        var now = new Date();
                        now.setMinutes(now.getMinutes() + 15); // timestamp
                        now = new Date(now); // Date object

                        $('#default_datetimepicker').datetimepicker({
                            format:'Y-m-d H:i',
                            minDate:'-1970/01/01', // today is minimum date
                            timepickerScrollbar:false,
                            mask:'9999-19-39 29:59',
                            value:now,
                           // theme:'dark'
                          });

                          $("#customer_name").val('');
                          $("#customer_phone").val('');
                        //   $("input[type=radio][name=size][value=1]").prop("checked", true);

                        //window.print();
                        // var url = '<?php //echo site_url('api/v1/user/generatePdf/')?>'+obj.id+'/'+obj.store+'/'+obj.counter;
                        // window.open(url);
                        //location.reload();
                    }
                },
                complete: function() {
                    $("#overlay").fadeOut(300);
                },
            });
        });

        $( ".cartButton" ).click(function() {
            // alert($("#form"+$(this).val()).serialize());
          
            $.ajax({
                method: "POST",
                data: $("#form"+$(this).val()).serialize(),
                url: "<?php echo site_url('cart/add_to_cart')?>",
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);　
                },
                success: function(data)
                {   
                    $('#noti_count').load("<?php echo site_url('cart/load_cart_count');?>");
                    $('#cart_total_price').load("<?php echo site_url('cart/load_cart_price');?>");

                    $('#detail_cart').html(data);
                    
                    $("#successModal").css('display','block');

                },
                complete: function() {
                    $("#overlay").fadeOut(300);
                },
            });
        

        });

    
        

        $('#detail_cart').load("<?php echo site_url('cart/load_cart');?>");
        $('#noti_count').load("<?php echo site_url('cart/load_cart_count');?>");
        $('#cart_total_price').load("<?php echo site_url('cart/load_cart_price');?>");
        $(".orderButton").prop("disabled", true);
 
        $( ".customCartButton" ).click(function() {
            // alert($("#customForm").serialize());

            var check = $("input[name=is_half]:checked").val();
            var other_items = $("#other_items").val();

            if(check==0 || (check==1 && other_items!='') ){
                $('#alert').fadeOut();
                $.ajax({
                    method: "POST",
                    data: $("#customForm").serialize(),
                    url: "<?php echo site_url('cart/add_to_cart')?>",
                    beforeSend: function() {
                        // setting a loader
                        $("#overlay").fadeIn(300);　
                    },
                    success: function(data)
                    {   
                        $('#noti_count').load("<?php echo site_url('cart/load_cart_count');?>");
                        $('#cart_total_price').load("<?php echo site_url('cart/load_cart_price');?>");
                        $('#detail_cart').html(data);
                        $(".add_ins").val('');
                        closeModal();
                        $("#checkoutModal").css('display','block');
                        //alert(data);
                        // var obj = JSON.parse(data);
                        // if(obj.success==1){

                        //     var url = '<?php //echo site_url('api/v1/user/generatePdf/')?>'+obj.id+'/'+obj.store+'/'+obj.counter;
                        //     window.open(url);
                        //     location.reload();
                        // }
                    },
                    complete: function() {
                        $("#overlay").fadeOut(300);
                    },
                });
            }else{
                $('#alert').fadeIn();
            }
           
        });

        $(document).on('click','.remove_cart',function(){
            var row_id=$(this).attr("id");
            
            $.ajax({
                url : "<?php echo site_url('cart/delete_cart');?>",
                method : "POST",
                data : {row_id : row_id},
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);　
                },
                success :function(data){
                    $('#noti_count').load("<?php echo site_url('cart/load_cart_count');?>");
                    $('#cart_total_price').load("<?php echo site_url('cart/load_cart_price');?>");
                    $('#detail_cart').html(data);
                },
                complete: function() {
                    $("#overlay").fadeOut(300);
                },
            });
             
        });

        $('input[type=radio][name=is_half]').change(function() {

            // cannot customize for mini size
            
            $('#alert_other').fadeOut();
            var dropdown = $('#other_items');
            if ($(this).val()==1) {
                if($('#custom_size').val()==1){
                    $(this).prop('checked', false);
                    $('#is_halfno').prop('checked', true);
                    $('#alert_other').fadeIn();
                }else{
                    dropdown.removeAttr('disabled');
                }
            }else{
                $('#alert').fadeOut();
                $('#extras2_heading').hide();
                $('#ingredients2_heading').hide();
                $("#item_plus").text('');
                $("#other_item_name").text('');
                $("#custom_add_item_name").val('');
                $('#custom_extras2').val('');
                $('#custom_ingredients2').val('');
                $('#custom_item_category2').val('');
                var checkboxes = $('#extras2');
                checkboxes.empty();
                var checkboxes = $('#extras4');
                checkboxes.empty();
                dropdown.val('');
                dropdown.attr('disabled','disabled');
            }
            calculate();
            
        });

        function calculate(){

            var is_half = $('input[type=radio][name=is_half]:checked').val();

            var qty = $('#custom_quantity').val();
            // var pizza1_price_single = $('#custom_extras_price').val();
            var pizza1_price_single = $('#custom_price_single').val();
            var pizza2_price_single = $('#other_items').find(':selected').data('price');

            var extras_price = 0;
            var extras1_label = '';
            $('.extrasCheckbox').each(function () {
                extras_price += this.checked ? $(this).data('price') : 0;
                extras1_label += this.checked ? $(this).data('title')+', ' : '';
            });
            extras1_label = extras1_label.replace(/,\s*$/, "");

            var extras_price2 = 0;
            var extras2_label = '';
            $('.extrasCheckbox2').each(function () {
                extras_price2 += this.checked ? $(this).data('price') : 0;
                extras2_label += this.checked ? $(this).data('title')+', ' : '';
            });
            extras2_label = extras2_label.replace(/,\s*$/, "");


            var extras_total_price = (extras_price + extras_price2) * qty;
            var custom_extras_price = (extras_price + extras_price2);
            var new_price = new_price2 = 0;
            var new_price_wf2 = new_price2_wf2 = 0;
            // if is half is yes
            if(is_half==1){
                // new_price = (Number(pizza1_price_single)/2)*qty;
                // new_price_wf2 = (Number(pizza1_price_single)/2);
                // price of other flavor
                // if(pizza2_price_single){
                //     new_price2 = (pizza2_price_single/2)*qty;
                //     new_price2_wf2 = (pizza2_price_single/2);
                // }
                if (pizza1_price_single < pizza2_price_single && pizza2_price_single) {
                    new_price = (Number(pizza2_price_single))*qty;
                    new_price_wf2 = (Number(pizza2_price_single));
                }
                else
                {
                    new_price = (pizza1_price_single)*qty;
                    new_price_wf2 = (pizza1_price_single);
                }
                custom_extras_price = custom_extras_price/2;
                extras_total_price = extras_total_price/2;
            }else{
                new_price = Number(pizza1_price_single)*qty;
                new_price_wf2 = Number(pizza1_price_single);
            }

            var total_price = new_price + new_price2 + extras_total_price;
            // var total_price_wf2 = new_price_wf2 + new_price2_wf2;
            var total_price_wf2 = new_price_wf2;


            // Change Values
            $('#custom_price').val(total_price);
            $('#custom_price_single_wf2').val(total_price_wf2);
            $('#custom_extras_price').val(custom_extras_price);
            $('#custom_extras1').val(extras1_label);
            $('#custom_extras2').val(extras2_label);
            $('#custom_price_text').hide().text('Pkr '+Math.round(total_price)+'/-').fadeIn('1000');

        }

        $('#other_items').change(function() {

            $("#item_plus").text('');
            $("#other_item_name").text('');
            $("#custom_add_item_name").val('');
            $('#custom_extras2').val('');
            $('#custom_ingredients2').val('');
            $('#custom_item_category2').val('');
            if ($(this).val()!='') {


                $("#item_plus").text('+');
                var add_item_name = $(this).find("option:selected").text();
                var add_item_category = $(this).find("option:selected").data('category');
                $("#other_item_name").text(add_item_name);
                $('#custom_add_item_name').val(add_item_name);
                $('#custom_item_category2').val(add_item_category);

                $('#alert').fadeOut();
                $.ajax({
                    method: "POST",
                    url: "<?php echo site_url('pizza_orders/get_extras/') ?>"+$(this).val(),
                    beforeSend: function() {
                        // setting a loader
                        $("#overlay").fadeIn(300);　
                    },
                    success: function(data)
                    {   
                        $('#extras2_heading').fadeIn();
                        data = $.parseJSON(data);
                        var checkboxes = $('#extras2');
                        checkboxes.empty();
                        $.each(data.extras, function(i, item) {
                            checkboxes.append('<li><input type="checkbox" class="extrasCheckbox2" data-price="'+item['price']+'" data-title="'+item['label']+'" value="'+item['value']+'" id="extras2'+item['value']+'" name="extras2[]"><label for="extras2'+item['value']+'">'+item['label']+'</label><div class="check"></div></li>')
                        });

                        $('#ingredients2_heading').fadeIn();
                        var checkboxes1 = $('#extras4');
                        checkboxes1.empty();
                        // var ingredients2_label = '';
                    
                        $.each(data.ingredients, function(i, item) {
                            checkboxes1.append('<li><input type="checkbox"class="ingredientsCheckbox2" data-price="'+item['price']+'" data-title="'+item['label']+'" value="'+item['value']+'" id="extras4'+item['value']+'" name="extras4[]"><label for="extras4'+item['value']+'">'+item['label']+'</label><div class="check"></div></li>');
                            // ingredients2_label += item['label']+', ';
                        });
                        // ingredients2_label = ingredients2_label.replace(/,\s*$/, "");
                        // $('#custom_ingredients2').val(ingredients2_label);

                        EnableCheckBoxClickFunction2();
                    },
                    complete: function() {
                        $("#overlay").fadeOut(300);
                    },
                });
            }else{

                $('#alert').fadeIn();
                $('#extras2_heading').hide();
                $('#ingredients2_heading').hide();
                var checkboxes = $('#extras2');
                checkboxes.empty();
                var checkboxes = $('#extras4');
                        checkboxes.empty();
            }
            calculate();
        });

        $('input[type=radio][name=size]').change(function() {
            if (this.checked) {

                var price_single =Number($(this).attr("data-price"));
                $('#price_single'+$(this).attr("data-id")).val(price_single);


                var price = Number($(this).attr("data-price")) * Number($('#quantity'+$(this).attr("data-id")).val());

                $('#price'+$(this).attr("data-id")).val(price);

                
            
                $('#price_text'+$(this).attr("data-id")).hide().text(price+'/-').fadeIn('1000');
                
            }
        });
        
        function EnableCheckBoxClickFunction(){
            $('.extrasCheckbox').click(function(){
                //console.log('working');
                calculate();
            });
            $('.ingredientsCheckbox').click(function(){
                //console.log('working');
                //calculate();

                var ingredients1_label = '';
                $('.ingredientsCheckbox').each(function () {
                    ingredients1_label += this.checked ? $(this).data('title')+', ' : '';
                });
                ingredients1_label = ingredients1_label.replace(/,\s*$/, "");
                $('#custom_ingredients1').val(ingredients1_label);

            });
        }

        function EnableCheckBoxClickFunction2(){
            $('.extrasCheckbox2').click(function(){
                //console.log('working');
                calculate();
            });
            $('.ingredientsCheckbox2').click(function(){
                //console.log('working');
                //calculate();

                var ingredients2_label = '';
                $('.ingredientsCheckbox2').each(function () {
                    ingredients2_label += this.checked ? $(this).data('title')+', ' : '';
                });
                ingredients2_label = ingredients2_label.replace(/,\s*$/, "");
                
                $('#custom_ingredients2').val(ingredients2_label);
                
            });
        }

        

        $(document).ready(function() {
            $('.minus').click(function () {
                var $input = $(this).parent().find('input');
                var count = parseInt($input.val()) - 1;
                count = count < 1 ? 1 : count;
                $input.val(count);
                $input.change();

                var price = $(".sizeRadio"+$input.attr("data-id")+":checked").attr('data-price');

                var price = Number(count)*Number(price);

                $('#price'+$input.attr("data-id")).val(price);
            
                $('#price_text'+$input.attr("data-id")).hide().text(price+'/-').fadeIn('1000');

                return false;
            });
            $('.plus').click(function () {
                var $input = $(this).parent().find('input');
                var count = parseInt($input.val()) + 1;
                $input.val(count);
                $input.change();

                var price = $(".sizeRadio"+$input.attr("data-id")+":checked").attr('data-price');

                var price = Number(count)*Number(price);

                $('#price'+$input.attr("data-id")).val(price);
            
                $('#price_text'+$input.attr("data-id")).hide().text(price+'/-').fadeIn('1000');

                return false;
            });
        });
    </script>
    <!-- <script src="jquery.datetimepicker.js"></script> -->
  
    <script type="text/javascript">
        // $.datetimepicker.setLocale('en');
        var now = new Date();
        now.setMinutes(now.getMinutes() + 15); // timestamp
        now = new Date(now); // Date object

        $('#default_datetimepicker').datetimepicker({
            format:'Y-m-d H:i',
            minDate:'-1970/01/01', // today is minimum date
            timepickerScrollbar:false,
            mask:'9999-19-39 29:59',
            value:now,
            // theme:'dark'
        });
    </script>
</body>
</html>
