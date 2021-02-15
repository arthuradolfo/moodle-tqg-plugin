<?php

class block_tqg_plugin_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $USER;

        $mform->addElement('text', 'config_hostname', get_string('hostname', 'block_tqg_plugin'));
        $mform->setDefault('config_hostname', 'localhost');
        $mform->setType('config_hostname', PARAM_RAW);
        $mform->addElement('text', 'config_port', get_string('port', 'block_tqg_plugin'));
        $mform->setDefault('config_port', '3000');
        $mform->setType('config_port', PARAM_RAW);
        $mform->addElement('text', 'config_user', get_string('user', 'block_tqg_plugin'));
        $mform->setDefault('config_user', $USER->username);
        $mform->setType('config_user', PARAM_RAW);
        $mform->addElement('text', 'config_email', get_string('email', 'block_tqg_plugin'));
        $mform->setDefault('config_email', $USER->email);
        $mform->setType('config_email', PARAM_RAW);
        $mform->addElement('text', 'config_password', get_string('password', 'block_tqg_plugin'));
        $mform->setDefault('config_password', 'password');
        $mform->setType('config_password', PARAM_RAW);
        $mform->addElement('text', 'config_threshold', get_string('threshold', 'block_tqg_plugin'));
        $mform->setDefault('config_threshold', '0.7');
        $mform->setType('config_threshold', PARAM_FLOAT);

    }
}