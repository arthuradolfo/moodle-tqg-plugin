<?php

class block_tqg_plugin extends block_base {
    public function init() {
        $this->title = get_string('simple_html', 'block_tqg_plugin');
    }

    public function get_content() {
        global $CFG, $COURSE, $OUTPUT, $USER, $DB;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->page->requires->js('/blocks/tqg_plugin/js/tqg_client.js');

        $this->content         =  new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        if (has_capability('mod/quiz:manage', context_course::instance($COURSE->id))) {
            $this->content->text = '<strong>' . get_string('options', 'block_tqg_plugin') . '</strong><br/>'
                .$OUTPUT->action_link(new moodle_url('/blocks/tqg_plugin/questions.php',
                    array('course_id' => $COURSE->id,
                        'email' => $this->config->email,
                        'hostname' => $this->config->hostname,
                        'port' => $this->config->port)),
                    get_string('questions', 'block_tqg_plugin')) . '<br/>';

            $this->content->text .= $OUTPUT->action_link(new moodle_url('/blocks/tqg_plugin/categories.php',
                    array('course_id' => $COURSE->id,
                        'email' => $this->config->email,
                        'hostname' => $this->config->hostname,
                        'port' => $this->config->port)),
                    get_string('categories', 'block_tqg_plugin')) . '<br/><br/>'
                .'<strong>' . get_string('connection_settings', 'block_tqg_plugin') . '</strong><br/>';

            if(!empty($this->config->hostname) && !empty($this->config->port)) {
                $this->content->text .= get_string('host_info', 'block_tqg_plugin') . $this->config->hostname . '<br/>'
                    .get_string('port_info', 'block_tqg_plugin') . $this->config->port . '<br/>'
                    .get_string('user_info', 'block_tqg_plugin') . $this->config->user . '<br/>'
                    .get_string('email_info', 'block_tqg_plugin') . $this->config->email . '<br/>';

                $token = $DB->get_record('tqg_login', array('user_email' => $this->config->email));
                if ($token) {
                    $this->content->text .= '<i>' . get_string('authenticated', 'block_tqg_plugin') . '</i>' . '<br/>';

                    $this->content->text .= $OUTPUT->action_link(new moodle_url('#'),
                            get_string('validate_token', 'block_tqg_plugin'),
                            new component_action('click', 'block_tqg_plugin_validate_token',
                                array('hostname' => $this->config->hostname,
                                    'port' => $this->config->port,
                                    'email' => $token->user_email,
                                    'token' => $token->user_token))) . '<br/>';

                    $this->content->text .= $OUTPUT->action_link(new moodle_url('#'),
                            get_string('refresh_token', 'block_tqg_plugin'),
                            new component_action('click', 'block_tqg_plugin_refresh_token',
                                array('hostname' => $this->config->hostname,
                                    'port' => $this->config->port,
                                    'email' => $this->config->email,
                                    'username' => $this->config->user,
                                    'password' => $this->config->password))) . '<br/>';

                    $this->content->text .= $OUTPUT->action_link(new moodle_url('#'),
                            get_string('update_password', 'block_tqg_plugin'),
                            new component_action('click', 'block_tqg_plugin_update_password',
                                array('hostname' => $this->config->hostname,
                                    'port' => $this->config->port,
                                    'token' => $token->user_token,
                                    'password' => $this->config->password))) . '<br/>';
                }
                else {
                    $this->content->text .= $OUTPUT->action_link(new moodle_url('#'),
                            get_string('register_user', 'block_tqg_plugin'),
                            new component_action('click', 'block_tqg_plugin_register_user',
                                array('hostname' => $this->config->hostname,
                                    'port' => $this->config->port,
                                    'email' => $this->config->email,
                                    'username' => $this->config->user,
                                    'password' => $this->config->password,
                                    'firstname' => $USER->firstname,
                                    'lastname' => $USER->lastname,
                                    'idnumber' => $USER->idnumber,
                                    'institution' => $USER->institution,
                                    'department' => $USER->department,
                                    'phone1' => $USER->phone1,
                                    'phone2' => $USER->phone2,
                                    'city' => $USER->city,
                                    'url' => $USER->url,
                                    'icq' => $USER->icq,
                                    'skype' => $USER->skype,
                                    'aim' => $USER->aim,
                                    'yahoo' => $USER->yahoo,
                                    'msn' => $USER->msn,
                                    'country' => $USER->country))) . '<br/>';

                    $this->content->text .= $OUTPUT->action_link(new moodle_url('#'),
                            get_string('login', 'block_tqg_plugin'),
                            new component_action('click', 'block_tqg_plugin_refresh_token',
                                array('hostname' => $this->config->hostname,
                                    'port' => $this->config->port,
                                    'email' => $this->config->email,
                                    'username' => $this->config->user,
                                    'password' => $this->config->password))) . '<br/>';
                }
            }
            else {
                $this->content->text .= get_string('set_connection', 'block_tqg_plugin');
            }
        }

        return $this->content;
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}