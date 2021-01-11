<?php

require(__DIR__.'/../../../config.php');

$course = required_param('course', PARAM_TEXT);
$port = required_param('port', PARAM_INT);
$email = required_param('email', PARAM_TEXT);
$token = required_param('token', PARAM_TEXT);

if ($token_aux = $DB->get_record('tqg_login', array('user_email' => $email))) {
    $token_aux->user_token = $token;
    $DB->update_record('tqg_login', $token_aux);
}
else {
    $token_aux = new stdClass();
    $token_aux->course = $course;
    $token_aux->port = $port;
    $token_aux->user_email = $email;
    $token_aux->user_token = $token;
    $DB->insert_record('tqg_login', $token_aux);
}
