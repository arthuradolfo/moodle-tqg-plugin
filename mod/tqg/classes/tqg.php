<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/questionlib.php';

class tqg
{
    const COMPONENT = 'tqg';
    const CAP_MANAGE = 'mod/tqg:manage';
    const PREFERRED_BEHAVIOR = 'deferredfeedback';
    private $cm;
    private $context;
    private $options;
    public $port;
    public $token;

    function __construct(stdClass $cm)
    {
        global $DB;

        $this->cm = $cm;
        $this->context = \context_module::instance($this->cm->id);
        $this->options = $DB->get_record('tqg', array('id' => $cm->instance));
        $tqg_login = $DB->get_record('tqg_login', array('course' => $this->__get("course")));
        $this->token = $tqg_login->user_token;
        $this->port = $tqg_login->port;
    }

    public function __get($name)
    {
        if (isset($this->options->$name))
            return $this->options->$name;

        throw new \coding_exception('Undefined property: '.$name);
    }

    public function has_capability($capability) {
        return has_capability($capability, $this->context);
    }

    public function require_capability($capability) {
        require_capability($capability, $this->context);
    }

    public function is_manager() {
        return $this->has_capability(self::CAP_MANAGE);
    }

    public function require_manager() {
        $this->require_capability(self::CAP_MANAGE);
    }

    /**
     *
     * @param int $tqgid
     * @return stdClass
     */
    public function create_session($tqgid) {
        global $DB, $USER;

        $session = new stdClass();
        $session->tqg_id = intval($tqgid);
        $session->questions = '';
        $session->number_questions = $this->__get("questions");

        $session->student_moodle_id = $USER->id;

        $quba = question_engine::make_questions_usage_by_activity('mod_tqg', $this->context);
        $quba->set_preferred_behaviour(self::PREFERRED_BEHAVIOR);
        question_engine::save_questions_usage_by_activity($quba);
        $session->questions_usage = $quba->get_id();
        $session->time_started = date("Y-m-d H:m:s", time());
        $session->category_moodle_id = intval($this->__get("questioncategory"));

        if ($this->token) {
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => json_encode($session),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n"
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents('http://host.docker.internal:' . $this->port . '/api/sessions', false, $context);
            var_dump($result);
            $response = json_decode($result);
            return $response;
        }

        return $session;
    }
}