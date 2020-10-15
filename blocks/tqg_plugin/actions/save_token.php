<?php

require(__DIR__.'/../../../config.php');

$email = required_param('email', PARAM_TEXT);
$token = required_param('token', PARAM_TEXT);

if ($token_aux = $DB->get_record('tqg_login', array('user_email' => $email))) {
    $token_aux->user_token = $token;
    $DB->update_record('tqg_login', $token_aux);
}
else {
    $token_aux = new stdClass();
    $token_aux->user_email = $email;
    $token_aux->user_token = $token;
    $DB->insert_record('tqg_login', $token_aux);
}
