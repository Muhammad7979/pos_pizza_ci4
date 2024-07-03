<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" href="<?php echo base_url('css/pizza_style.css'); ?>">
    <title>Order Status</title>
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

    .order_status .new_status_menu{position: relative;}
    .tehzeeb-logo{position: absolute;left: 0;right: 0; top: -18px;margin: auto;}

    .pizza_menu_block .menu_categories{  padding: 35px 0px; /*padding-top: 60px;*/}

    </style>
    <!-- <div id="mydiv">
      <div id="mydivheader" onclick="openFullscreen()">
        <img src="<?php //echo base_url('images/expand-arrows.png'); ?>" alt="">
      </div>
    </div> -->
    <div class="tehzeeb_pizza_menu" id="fullScreen">
        <div class="order_status pizza_menu_block">
            <div class="inner_container">

                <div class="menu_categories new_status_menu">
                    <ul>

                        <li><span style=" margin-right: 10px;">Processing </span> <img src="<?php echo base_url('images/making.png'); ?>" style="top: 0px; position: absolute;"></li>
                        <li  class="tehzeeb-logo"><img src="<?php echo base_url('images/tehzeeb_logo.png'); ?>"></li>


                        <li><span style=" margin-right: 12px;">Completed </span> <img src="<?php echo base_url('images/final.png'); ?>" style="top: 6px; position: absolute;width:43px;"></li>

                    </ul>
                </div>


                <div class="order_summary">

                    <div class="left_in_process_table">
                        <table id="processed_table">
                           <!--  <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Flavor</th>
                                    <th>Action</th>
                                </tr>
                            </thead> -->
                            <tbody>
                                <?php 
                                    $last_mt = 0;
                                    if(!empty($processed_orders)){
                                    foreach ($processed_orders as $key => $value) { 
                                        //latest micro time
                                        $last_mt = $value->microtime;
                                ?>
                                <tr>
                                    <td>
                                        <!-- <input type="hidden" name="microtime" value="<?php //echo $value->microtime ?>"> -->
                                        <input type="hidden" name="order_id" value="<?php echo $value->order_id ?>"> 
                                        <input type="hidden" class="process_input" id="processed_id_<?php echo $value->order_id ?>" value="<?php echo $value->order_id ?>" />
                                        <?php echo $value->order_number ?>
                                    </td>
                                    <!-- <td><?php //echo $value->name ?></td> -->
                                    <!-- <td>
                                        <button type="button" class="complete_btn" value="<?php //echo $value->order_id ?>">Complete</button>
                                    </td> -->
                                </tr>
                                <?php }}else{ ?>
                                    <tr>
                                        <td>No Order Found</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <input type="hidden" name="processed_last_mt" id="processed_last_mt" value="<?php echo $last_mt ?>">
                    </div>

                    <div class="right_complete_table">
                        <table id="completed_table">
                            <!-- <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Action</th>
                                </tr>
                            </thead> -->
                            <tbody>
                                <?php 
                                    $last_mt = 0;
                                    if(!empty($completed_orders)){
                                    foreach ($completed_orders as $key => $value) { 
                                        //latest micro time
                                        $last_mt = $value->microtime;
                                ?>
                                <tr>
                                    <td>
                                        <input type="hidden" class="complete_input" id="completed_id_<?php echo $value->order_id ?>" value="<?php echo $value->order_id ?>" />
                                        <?php echo $value->order_number ?>
                                    </td>
                                    <!-- <td><?php //echo $value->name ?></td> -->
                                </tr>
                                <?php }}else{ ?>
                                    <tr>
                                        <td>No Order Found</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <input type="hidden" name="completed_last_mt" id="completed_last_mt" value="<?php echo $last_mt ?>">
                    </div>

                </div>

            </div>
        </div>
    </div>


    
    
<!--     <script
    src="https://code.jquery.com/jquery-3.4.1.min.js"
    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
    crossorigin="anonymous"></script> -->

    <script src="<?php echo base_url('js/jquery.js'); ?>"></script>

    <!-- Pusher Notification -->
    <script src="https://js.pusher.com/5.0/pusher.min.js"></script>

    <script type="text/javascript">

        // Enable pusher logging - don't include this in production
        // Pusher.logToConsole = true;

        var pusher = new Pusher('d74cdee3f051d856e9f7', 
        {
          cluster: 'ap1',
          // forceTLS: true
        });

        var channel = pusher.subscribe('pusher-channel');

        // Pusher Channel for pizza order remove
        channel.bind('order-remove-event', function(data) {

			$("#processed_id_"+data.order_id).parent('td').parent('tr').remove();
            if($(".process_input").length==0 && $('#processed_last_mt').val()!=0){
                $('#processed_last_mt').val(0);
                $('#processed_table tbody').append('<tr><td>No Order Found</td></tr>').hide().fadeIn();
            }
            
            $("#completed_id_"+data.order_id).parent('td').parent('tr').remove();
            if($(".complete_input").length==0 && $('#completed_last_mt').val()!=0){
                $('#completed_last_mt').val(0);
                $('#completed_table tbody').append('<tr><td>No Order Found</td></tr>').hide().fadeIn();
            }

        });

    </script>

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
        $(document).ready(function() {
            function getProcessedRow() {

                var processed_last_mt = $('#processed_last_mt').val();
                var status = 'Inprocess';
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url('pizza_orders_status/get_updated_data/') ?>"+processed_last_mt+"/"+status,
                    success: function(data)
                    {   
                        if(data!='null'){
                            if(processed_last_mt==0){
                                $('#processed_table tbody').html('');
                            }
                            data = $.parseJSON(data);
                            // alert($("#processed_id_" + data.order_id).length);
                            if ($("#processed_id_" + data.order_id).length == 0)
                            {
                                //alert('append');
                                $('#processed_table tbody').append('<tr><td><input type="hidden" name="order_id" value="'+data.order_id+'"><input type="hidden" class="process_input" id="processed_id_'+data.order_id+'" value="'+data.order_id+'" />'+data.order_number+'</td></tr>');
                                $('#processed_id_'+data.order_id).parent('td').parent('tr')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                                $('#processed_last_mt').val(data.microtime);
                            }
                        }
                        
                    }
                });
            }

            function getCompletedRow() {

                var completed_last_mt = $('#completed_last_mt').val();
                var status = 'Completed';
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url('pizza_orders_status/get_updated_data/') ?>"+completed_last_mt+"/"+status,
                    success: function(data)
                    {   

                        if(data!='null'){
                            if(completed_last_mt==0){
                                $('#completed_table tbody').html('');
                            }
                            data = $.parseJSON(data);

                            if ($("#completed_id_" + data.order_id).length == 0)
                            {
                                //alert('append');
                                $('#processed_id_'+data.order_id).parent('td').parent('tr').fadeOut().remove();
                                
                                $('#completed_table').append('<tr><td><input type="hidden" class="complete_input" id="completed_id_'+data.order_id+'" value="'+data.order_id+'" />'+data.order_number+'</td></tr>');
                                $('#completed_id_'+data.order_id).parent('td').parent('tr')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                                $('#completed_last_mt').val(data.microtime);
                            }
                            // if($(".process_input").length==0){
                            if($(".process_input").length==0 && $('#processed_last_mt').val()!=0){
                                $('#processed_last_mt').val(0);
                                $('#processed_table tbody').append('<tr><td>No Order Found</td></tr>').hide().fadeIn();
                            }
                        }
                    }
                });
            }

            setInterval(function () {
               getProcessedRow();
               getCompletedRow();
            }, 1000);

        });
        // $(document).ready(function() {
        //     function getProcessedRow() {

        //         var processed_last_mt = $('#processed_last_mt').val();
        //         var status = 'Inprocess';
        //         $.ajax({
        //             type: "POST",
        //             url: "<?php // echo base_url('pizza_orders_status/get_updated_data/') ?>"+processed_last_mt+"/"+status,
        //             success: function(data)
        //             {   
                        
        //                 if(data!='null'){
        //                     if(processed_last_mt==0){
        //                         $('#processed_table tbody').html('');
        //                     }
        //                     data = $.parseJSON(data);
        //                     // alert($("#processed_id_" + data.order_id).length);
        //                     if ($("#processed_id_" + data.order_id).length == 0)
        //                     {
        //                         //alert('append');
        //                         $('#processed_table tbody').append('<tr><td><input type="hidden" class="process_input" id="processed_id_'+data.order_id+'" value="'+data.order_id+'" />'+data.order_number+'</td></tr>');
        //                         $('#processed_id_'+data.order_id).parent('td').parent('tr')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        //                         $('#processed_last_mt').val(data.microtime);

        //                     }
        //                 }
        //                 if($(".process_input").length==0){
        //                 	$('#processed_last_mt').val(0)
        //                     $('#processed_table tbody').html('<tr><td>No Order Found</td></tr>');
        //                 }
        //             }
        //         });
        //     }

        //     function getCompletedRow() {

        //         var completed_last_mt = $('#completed_last_mt').val();
        //         var status = 'Completed';
        //         $.ajax({
        //             type: "POST",
        //             url: "<?php // echo base_url('pizza_orders_status/get_updated_data/') ?>"+completed_last_mt+"/"+status,
        //             success: function(data)
        //             {   

        //                 if(data!='null'){
        //                     if(completed_last_mt==0){
        //                         $('#completed_table tbody').html('');
        //                     }
        //                     data = $.parseJSON(data);

        //                     if ($("#completed_id_" + data.order_id).length == 0)
        //                     {
        //                         //alert('append');
        //                         $('#processed_id_'+data.order_id).parent('td').parent('tr').fadeOut().remove();
                                
        //                         $('#completed_table').append('<tr><td><input type="hidden" class="complete_input" id="completed_id_'+data.order_id+'" value="'+data.order_id+'" />'+data.order_number+'</td></tr>');
        //                         $('#completed_id_'+data.order_id).parent('td').parent('tr')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        //                         $('#completed_last_mt').val(data.microtime);
        //                     }
                            
        //                 }
        //                 if($(".complete_input").length==0){
        //                 	$('#completed_last_mt').val(0)
        //                     $('#completed_table tbody').html('<tr><td>No Order Found</td></tr>');
        //                 }
        //             }
        //         });
        //     }

        //     setInterval(function () {
        //        getProcessedRow();
        //        getCompletedRow();
        //     }, 1000);

        // });
        

    </script>
    <script type="text/javascript">
        $(document).keypress(function(e){
            var keycode = (e.keyCode ? e.keyCode : e.which);
            console.log(keycode);
            var id = $("input[name=order_id]").first().val();

            var status = '';
            if(keycode == '49' || keycode == '97' || keycode == '35'){
                status = "Completed"
                // Accept First Row
            }else if(keycode == '50' || keycode == '98' || keycode == '40'){
                status = "Inprocess"
                // Skip First Row
            }
            if(status!='')
            {
                $("input[name=order_id]").first().parent('td').parent('tr').toggle("slow", function() {});
               if(id != null)
               { 
                window.location.href = "<?php echo base_url('pizza_orders_status/update_status/') ?>"+id+'/'+status;
               }
            }
        });
    </script>
</body>
</html>
