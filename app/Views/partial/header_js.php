<?php 
$security = \Config\Services::security();
?>
<script>
  // Update the clock display every second
setInterval(updateClock, 1000);

// Function to update the clock display
function updateClock() {
  var currentTime = moment().format("DD/MM/YYYY h:mm:ss A");

  // Update the clock display element with the current time
  var clockElement = document.getElementById('liveclock');
  if (clockElement) {
    clockElement.innerHTML = currentTime;
  }
}

var post = $.post;

$.notifyDefaults({
	placement: {
		from: '<?php echo $appData['notify_vertical_position'] ?>',
        align: '<?php echo $appData['notify_horizontal_position'] ?>'

	}});



        
// 
var csrf_form_base = function() {
    return { '<?= csrf_token() ?>': function() { return csrf_token(); } };
};

var csrf_token = function() {
    // Retrieve the CSRF cookie name
    var csrf_cookie_name = '<?= $security->getCookieName() ?>';

    // Retrieve the CSRF token value from the cookie
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();
        if (cookie.indexOf(csrf_cookie_name + '=') === 0) {
            return cookie.substring(csrf_cookie_name.length + 1);
        }
    }

    return null; // CSRF token not found
};

$.post = function() {
    arguments[1] = $.extend(arguments[1], csrf_form_base());
    post.apply(this, arguments);
};

var setup_csrf_token = function() {
    $('input[name="<?= csrf_token() ?>"]').val(csrf_token());
};

setup_csrf_token();

$.ajaxSetup({
    dataFilter: function(data) {
        setup_csrf_token();
        return data;
    }
});

session_sha1 = '<?= session('session_sha1') ?>';

</script>