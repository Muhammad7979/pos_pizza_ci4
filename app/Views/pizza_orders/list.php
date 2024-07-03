<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
	<link rel="stylesheet" href="<?php echo base_url('css/pizza_style.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('css/pizza_responsive.css'); ?>">
	<title>Order Status</title>
	<script src="js/moment.js"></script>
</head>

<body>
	<style>
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
	
	</style>
	<!-- <div id="mydiv">
	  <div id="mydivheader" onclick="openFullscreen()">
	  	<img src="<?php //echo base_url('images/expand-arrows.png'); ?>" alt="">
	  </div>
	</div> -->
	<div class="tehzeeb_pizza_menu" id="fullScreen">
		<div class="order_status pizza_menu_block">
			<div class="inner_container inner_container_width">

				<div class="logo">
					<img src="<?php echo base_url('images/tehzeeb_logo.png'); ?>" alt="">
				</div>

				<div class="order_table">

					<table>
						<thead>
							<tr class="header">
								<!-- <th>Date</th> -->
								<th>Token</th>
								<th>Name</th>
								<th>Size</th>
								<th>Quantity</th>
								<th>Crust</th>
								<th>Dough</th>
								<th >Details</th>
								<th>Deliver Time</th>
								<!-- <th></th> -->
							</tr>
						</thead>
						<tbody id="append_new_order<?php echo $store_id ?>">
						<?php 
							if($pizza_items) {
							foreach ($pizza_items as $key => $value) { 
						?>							
							<tr class="order_row_<?php echo $value->order_id ?>">
								<input type="hidden" name="microtime" value="<?php echo $value->microtime ?>">
								<input type="hidden" name="order_id" value="<?php echo $value->order_id ?>">
								<td class="showhr"><?php echo $value->order_number ?></td>
								<td class="details"> 
									<table>
										<tbody>
											<?php 
												$style = '';
												if(count($value->items)>1){
													$style = 'style="margin:5px 0px"';
												}
												foreach ($value->items as $in => $item) {  
											?>
											
												<tr>
												<td><?php echo $item->name ?><?php if($item->is_half=='Yes'){ echo " + ".$item->flavor2; } ?></td>
												<td><?php echo $item->size ?></td>
												<td><?php echo parse_decimals($item->quantity) ?></td>
												<td><?php echo $item->layer ?></td>
												<td><?php echo $item->dough ?></td>
												<td>
													<?php if($item->ingredients1_title){ ?>
													<span>Remove:</span> <?php echo $item->ingredients1_title ?><br>
													<?php } ?>
													<?php if($item->extras1_title){ ?>
													<span>Add:</span> <?php echo $item->extras1_title ?><br>
													<?php } ?>

													<?php if($item->is_half=='Yes'){ ?>
														<?php if($item->ingredients2_title){ ?>
														<span>Flavor 2 Remove:</span> <?php echo $item->ingredients2_title ?><br>
														<?php } ?>
														<?php if($item->extras2_title){ ?>
														<span> Flavor 2 Add:</span> <?php echo $item->extras2_title ?><br>
														<?php } ?>
													<?php } ?>

													<?php if($item->item_description){ ?>
													<span>Description:</span> <?php echo $item->item_description ?><br>
													<?php } ?>
												</td>

												<td>
        <?php
        $deliveryTimestamp = strtotime($value->deliver_at);
        $formattedDeliveryTime = date('Y-m-d h:i:s A l', $deliveryTimestamp);

        // Check if the delivery time is less than the current time
        $isExpired = strtotime('now') > $deliveryTimestamp;

         echo $formattedDeliveryTime;
        ?>
    </td>
			
											</tr>
											<?php } ?>
										</tbody>
									</table>
								</td>
							</tr>
						<?php }} ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

<!-- 	<script
	src="https://code.jquery.com/jquery-3.4.1.min.js"
	integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
	crossorigin="anonymous"></script> -->
	<script src="<?php echo base_url('js/jquery.js'); ?>"></script>

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
	
	<!-- Pusher Notification -->
    <script src="https://js.pusher.com/5.0/pusher.min.js"></script>

    <script type="text/javascript">

        // Enable pusher logging - don't include this in production
        // Pusher.logToConsole = true;

        var pusher = new Pusher('d74cdee3f051d856e9f7', {
          cluster: 'ap1',
          // forceTLS: true
        });

        var channel = pusher.subscribe('pusher-channel');

        // Pusher Channel for pizza orders
        channel.bind('order-event', function(data) {

        	var order_items = '';
        	$.each(data.items, function(i, item) {
        		var extras = '';
        		var ingredients = '';
        		var extras2 = '';
        		var ingredients2 = '';
        		if(item.is_half=='Yes'){
        			if(item.ingredients1_title!=''){
        				ingredients = '<br><span>Remove: </span>'+item.ingredients1_title;
        			}
        			if(item.ingredients2_title!=''){
        				ingredients2 = '<br><span>Flavor 2 Remove: </span>'+item.ingredients2_title;
        			}
        			if(item.extras1_title!=''){
        				extras = '<br><span>Add: </span>'+item.extras1_title;
        			}
        			if(item.extras2_title!=''){
        				extras2 = '<br><span>Flavor 2 Add: </span>'+item.extras2_title;
        			}
        			order_items += '<tr><td>'+item.name+' + '+item.flavor2+'<span> | </span>'+item.size+'<span> | </span>'+Math.round(item.quantity)+'<span> | </span>'+item.layer+'<span> | </span>'+item.dough+ingredients+extras+ingredients2+extras2+'</td></tr>';
        		}else{
        			if(item.extras1_title!=''){
        				extras = '<br><span>Add: </span>'+item.extras1_title;
        			}
        			if(item.ingredients1_title!=''){
        				ingredients = '<br><span>Remove: </span>'+item.ingredients1_title;
        			}
        			order_items += '<tr><td>'+item.name+'<span> | </span>'+item.size+'<span> | </span>'+Math.round(item.quantity)+'<span> | </span>'+item.layer+'<span> | </span>'+item.dough+ingredients+extras+'</td></tr>';
        		}

        	});

           	$('#append_new_order'+data.store_id).append('<tr class="order_row_'+data.order_id+'"><input type="hidden" name ="microtime" value="'+data.microtime+'" ><input type="hidden" name ="order_id" value="'+data.order_id+'" ></td><td>'+data.order_number+'</td><td>'+Math.round(data.order_quantity)+'</td><td>'+data.order_status+'</td><td class="details"><table><tbody>'+order_items+'</tbody></table></td></tr>');

        });

        // Pusher Channel for pizza order remove
        channel.bind('order-remove-event', function(data) {
        	$("input[name=order_id][value="+data.order_id+"]").first().parent('tr').fadeOut("slow", function() {
				$("input[name=order_id][value="+data.order_id+"]").first().parent('tr').remove();
			});
        });

    </script>

    <script type="text/javascript">        

		$(document).on('keypress', function(e) {
		   	e.preventDefault(); 
			var keyCode = (e.keyCode ? e.keyCode : e.which);
			if (e.keyCode!==8)
			trigger_keypress(keyCode);
		 }).on('keydown', function(e) {
		 	// keydown event only works for numeric pad backspace button
		 	var keyCode = (e.keyCode ? e.keyCode : e.which);
		   	if (keyCode==8)
		    trigger_keypress(e.keyCode);
		 });

		function trigger_keypress(keyCode) {
			var microtime = $("input[name=microtime]").first().val();
		    var id = $("input[name=order_id]").first().val();
			

		    var status = '';

		    // if key code numpad 1 or 2 or 3 or 0 or enter or dot 
		 	if ($.inArray( keyCode, [ 49, 50, 51, 13, 48, 46 ] ) !== -1){
		    	status = "Inprocess"
		        // Accept First Row

		    }else 
		    // if key code numpad 4 or 5 or 6 or 7 or 8 or 9 or + 
		 	if ($.inArray( keyCode, [ 55, 56, 57, 43, 52, 53, 54, 8 ] ) !== -1){
		    	 status = "Pending"
		    	//status = "Inprocess"
		        // Skip First Row
		    }else 
		    // if key code numpad / or * or -
		 	if ($.inArray( keyCode, [ 47, 42, 45 ] ) !== -1){
		    	status = "Rejected"
		    	// status = "Inprocess"
		        // Reject First Row
		    }
		    if(id>0){
		    	$("input[name=order_id]").first().parent('tr').fadeOut("slow", function() {
					$("input[name=order_id]").first().parent('tr').remove();
				});
            	$.ajax({
	                type: "POST",
	                url: "<?php echo base_url('pizza_orders_list/update_status/') ?>"+id+'/'+microtime+'/'+status,
	                beforeSend: function() {
	                    // setting a loader
	                    $("#overlay").fadeIn(300);　
	                },
	                success: function(data)
	                {   
	                	//location.reload();
	                }
	            });
        	}

		    //window.location.href = "<?php //echo base_url('pizza_orders_list/update_status/') ?>"+id+'/'+microtime+'/'+status;
		}

		function getLatestOrders(){
			$.ajax({
                type: "POST",
                url: "<?php echo base_url('pizza_orders_list/getLatestOrders/') ?>",
                beforeSend: function() {
                    // setting a loader
                    $("#overlay").fadeIn(300);
                },
                success: function(data)
                {   
                	data = $.parseJSON(data);
                	$.each(data.pizza_items, function(i, idata) {
                		if($("input[name=order_id][value="+idata.order_id+"]").length==0){
                		var order_items = '';

                		var style = '';
						if(idata.items.length>1){
							// style = 'style="margin:5px 0px"';
						}
                		$.each(idata.items, function(i, item) {
			        		var extras = '';
			        		var ingredients = '';
			        		var extras2 = '';
			        		var ingredients2 = '';
			        		if(item.is_half=='Yes'){
			        			if(item.ingredients1_title!=''){
			        				ingredients = '<br><span>Remove: </span>'+item.ingredients1_title;
			        			}
			        			if(item.ingredients2_title!=''){
			        				ingredients2 = '<br><span>Flavor 2 Remove: </span>'+item.ingredients2_title;
			        			}
			        			if(item.extras1_title!=''){
			        				extras = '<br><span>Add: </span>'+item.extras1_title;
			        			}
			        			if(item.extras2_title!=''){
			        				extras2 = '<br><span>Flavor 2 Add: </span>'+item.extras2_title;
			        			}
			        			// order_items += '<tr '+style+'><td style="width: 20%;">'+item.name+' + '+item.flavor2+'</td><td>'+item.size+'</td><td>'+Math.round(item.quantity)+'</td><td>'+item.layer+'</td><td>'+item.dough+'</td><td style="width: 30%;">'+ingredients+extras+ingredients2+extras2+'</td></tr>';

								order_items += '<tr><td>'+item.name+'</td><td>'+item.size+'</td><td>'+Math.round(item.quantity)+'</td><td>'+item.layer+'</td><td>'+item.dough+'</td><td>'+ingredients+extras+
                                (item.item_description)? `<br/><span>Description</span>: ${item.item_description}`:''+
                                 '</td><td>'+moment(idata.deliver_at).format('YYYY-MM-DD hh:mm:ss A')+'</td></tr>';
			        			//order_items += '<tr><td>'+item.name+' + '+item.flavor2+'<span> | </span>'+item.size+'<span> | </span>'+Math.round(item.quantity)+'<span> | </span>'+item.layer+'<span> | </span>'+item.dough+ingredients+extras+ingredients2+extras2+'</td></tr>';
			        		}else{
			        			if(item.extras1_title!=''){
			        				extras = '<br><span>Add: </span>'+item.extras1_title;
			        			}
			        			if(item.ingredients1_title!=''){
			        				ingredients = '<br><span>Remove: </span>'+item.ingredients1_title;
			        			}
			        			// order_items += '<tr '+style+'><td style="width: 20%;">'+item.name+'</td><td>'+item.size+'</td><td>'+Math.round(item.quantity)+'</td><td>'+item.layer+'</td><td>'+item.dough+'</td><td style="width: 30%;">'+ingredients+extras+'</td></tr>';
								order_items += '<tr><td>'+item.name+'</td><td>'+item.size+'</td><td>'+Math.round(item.quantity)+'</td><td>'+item.layer+'</td><td>'+item.dough+'</td><td>'+ingredients+extras+ 
    ((item.item_description)? `<br/><span>Description</span>: ${item.item_description}`:'')+

								'</td><td>'+ moment(idata.deliver_at).format('YYYY-MM-DD hh:mm:ss A')+'</td></tr>';
			        			// order_items += '<tr><td>'+item.name+'<span> | </span>'+item.size+'<span> | </span>'+Math.round(item.quantity)+'<span> | </span>'+item.layer+'<span> | </span>'+item.dough+ingredients+extras+'</td></tr>';
			        		}

			        	});

                		$('#append_new_order'+idata.store_id).append('<tr class="order_row_'+idata.order_id+'"><input type="hidden" name ="microtime" value="'+idata.microtime+'" ><input type="hidden" name ="order_id" value="'+idata.order_id+'" ></td><td class="showhr">'+idata.order_number+'</td><td class="details"><table><tbody>'+order_items+'</tbody></table></td></tr>');
                		}
                	});

                }
            });
		}

		$(document).ready(function(){
			// interval for every 30 minutes
			// setInterval(getLatestOrders,1000);
			setInterval(getLatestOrders,0.15 * 60 * 1000);
		});
	</script>
</body>
</html>
