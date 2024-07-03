<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <?php $config = config(Config\AppConfig::class); ?>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <base href="<?= base_url(); ?>"/>
    <title><?= $config->company . ' | '. lang('login_lang.login_login'); ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" type="text/css"
          href="<?= 'dist/bootswatch/' . (empty($config->theme) ? 'flatly' : $config->theme) . '/bootstrap.min.css' ?>"/>

          <!-- start css template tags -->
    <link rel="stylesheet" type="text/css" href="dist/style.css"/>
    <!-- end css template tags -->
</head>

<body>

<div class="wrapper">
    <section id="body">
        <div class="login_page">
            <div class="content_block">
                <div id="logo"><a href="<?php  echo base_url() ?>"><img src="images/tehzeeb/logo_login_page.png" alt=""></a></div>
                <div id="login">
                    <?php echo form_open('login') ?>
                    <div id="container">
                    <?php if (!empty($errors)) : ?>
                      <div align="center" style="color:red">
                        <?= implode('<br>', $errors) ?>
                      </div>
                    <?php endif; ?>
                        <div class="input-group">
                            <div class="glyphicon"><img src="images/tehzeeb/username_icon.png" alt=""></div>

                            <input class="form-control" placeholder="<?= lang('login_lang.login_username') ?>"
                                   name="username" type="text" autofocus/>

                            <div class="clear"></div>
                        </div>
                        <div class="input-group">
                            <div class="glyphicon"><img src="images/tehzeeb/password_icon.png" alt=""></div>
                            <input class="form-control" placeholder="<?= lang('login_lang.login_password') ?>"
                                   name="password" type="password"/>

                            <div class="clear"></div>
                        </div>
                        <input class="btn" type="submit" name="loginButton" value="Login"/>
                    </div>
                    <?php  echo form_close(); ?>
                </div>

            </div>
            <!--content_block-->

        </div>
        <!--login_page-->
    </section>

    <!--footer-->
    <footer id="footer" style="position: fixed; display: block;
    bottom: 0;">
        <p><span><a href="http://www.tehzeeb.com" target="_blank">www.tehzeeb.com</a></span>
            <?= lang('common_lang.common_you_are_using_ospos') ." | "
           //     . $this->gu->getCacheSalesCount(); ?>
        </p>
    </footer>
</div>


</body>
</html>
