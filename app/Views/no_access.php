<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<?php
echo lang('error_lang.error_no_permission_module').' '.$module_name . (!empty($permission_id) ? ' (' . $permission_id . ')' : '').'. '; 
?>
<a href="<?= base_url('home'); ?>">Go back to home.</a>
</body>
</html>
 
