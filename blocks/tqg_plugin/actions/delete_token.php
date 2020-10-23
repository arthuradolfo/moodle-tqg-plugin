<?php

require(__DIR__.'/../../../config.php');

$email = required_param('email', PARAM_TEXT);

$DB->delete_records('tqg_login', array('user_email' => $email));
