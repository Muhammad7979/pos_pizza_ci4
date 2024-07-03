</section>
<div class="clear"></div>
<?php
if($controller_name == 'categories' || $controller_name == 'items'){
    $position = 'relative';
}else{
    $position = 'fixed';
}
?>
<!--footer-->
<footer id="footer" style="position: <?= $position ?>;">
        <strong><?php
            echo lang('common_lang.common_you_are_using_ospos') ." | "
            . $gu->getCacheSalesCount(); ?></strong>
</footer>

</div>
<!--wrapper-->
       
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
    channel.bind('notificaton-event', function(data) {
      // alert(JSON.stringify(data));
      var oldCount = $('#notificationsCount'+data.user).html();
      var newCount = parseInt(oldCount)+1;
      $('#notificationsCount'+data.user).html(newCount);
      $('#para'+data.user).remove();
      $('#newNotifications'+data.user).prepend('<li><a href="'+data.url+'" class="text-primary"><p>'+data.message+'<span class="timeline-date">'+data.date+'</span></p></a></li>');
    });

</script>

</body>
</html>
