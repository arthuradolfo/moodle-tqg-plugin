<?php
defined('MOODLE_INTERNAL') || die();

class tqg_session {
    const STATUS_STARTED = 1;
    const STATUS_ASKED = 2;
    const STATUS_ANSWERED = 3;
    const STATUS_FINISHED = 4;

    public $session;
    private $id;
    private $questions_usage;
    private $questions;
    public $current_question;
    private $port;
    private $token;
    private $tqg;
    private $cm;
    private $user;

    /**
     *
     * @param int $session_id
     */
    public function __construct($session_id, $token, $port) {
        global $DB;

        $this->token = $token;
        $this->port = $port;

        if($this->token) {
            $options = array(
                'http' => array(
                    'method'  => 'GET',
                    'header'=>  "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Bearer ". $this->token ."\r\n"
                )
            );

            $context  = stream_context_create( $options );
            $result = file_get_contents( 'http://host.docker.internal:'.$this->port.'/api/sessions/'.$session_id, false, $context );
            $response = json_decode( $result );
            $this->session = $response->data;

            $context  = stream_context_create( $options );
            $result = file_get_contents( 'http://host.docker.internal:'.$this->port.'/api/user', false, $context );
            $response = json_decode( $result );
            $this->user = $response;

            $this->id = $this->session->id;
            $this->questions_usage = $this->session->questions_usage;

            $tqg = $DB->get_record('tqg', array('id' => $this->session->tqg_id));
            $cm = get_coursemodule_from_instance('tqg', $tqg->id);

            $this->tqg = new tqg($cm);
            $this->cm = $cm;

            $this->questions = array();
            $this->current_question = null;
            if ($this->session->questions) {
                $this->questions = explode(".", $this->session->questions);
                $this->current_question = end($this->questions);
            }
        }
    }

    protected function update()
    {
        if ($this->token) {
            $options = array(
                'http' => array(
                    'method' => 'PUT',
                    'content' => json_encode($this->session),
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n"
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents('http://host.docker.internal:' . $this->port . '/api/sessions/'.$this->session->id, false, $context);
            return json_decode($result);
        }
        return "";
    }

    /**
     *
     * @return object
     */
    public function get_next_question() {
        global $DB;

        if ($this->token) {
            $options = array(
                'http' => array(
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n"
                )
            );

            $context = stream_context_create($options);
            $result = file_get_contents('http://host.docker.internal:' . $this->port . '/api/sessions/'. $this->session->id .'/get_next_question', false, $context);
            $response = json_decode($result);

            $this->questions[] = $response->data->moodle_id;

            $this->session->questions = implode(",", $this->questions);
            $this->session->current_question = $response->data->moodle_id;
            $this->session->status = self::STATUS_ASKED;
            $this->update();
            $this->current_question = $response->data->moodle_id;

            $quba = question_engine::load_questions_usage_by_activity($this->questions_usage);
            $questions = question_load_questions(array($this->current_question));
            $qobj = question_bank::make_question(reset($questions));
            $slot = $quba->add_question($qobj);
            $quba->start_question($slot);
            question_engine::save_questions_usage_by_activity($quba);

            $this->session->slot = $slot;
            $this->update();

            return $response;
        }
    }

    /**
     *
     * @param int $questionid
     * @return string
     */
    public function render_question($session_id) {
        global $PAGE;

        $quba = question_engine::load_questions_usage_by_activity($this->questions_usage);

        $output = '';
        $output .= html_writer::tag('h4', get_string('question').' '.$this->session->slot);

        $output .= html_writer::start_tag('form',
            array('action' => 'attempt.php', 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'
            ));
        $output.='<input type=hidden name=cmid value='.$this->cm->id.'>';
        $output.='<input type=hidden name=session value='.$this->id.'>';
        $options = new question_display_options();
        $options->marks = question_display_options::MAX_ONLY;
        $options->markdp = 2; // Display marks to 2 decimal places.
        $options->feedback = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::HIDDEN;

        $output.= $quba->render_question($this->session->slot, $options);
        $output .= html_writer::start_tag('div', array('class' => 'submitbtns'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'next',
            'value' => get_string('next')));
        $output .= html_writer::end_tag('div');
        $output.=html_writer::end_tag('form');

        return $output;
    }

    public function process_session() {
        $quba = question_engine::load_questions_usage_by_activity($this->questions_usage);
        $slot=$this->session->slot;
        $submitteddata=$quba->extract_responses($slot);

        $quba->process_action($slot,$submitteddata);
        $quba->finish_question($slot);

        $attempt = $quba->get_question_attempt($slot);
        $this->session->last_response = ($attempt->get_mark() > $this->user->threshold) ? 1 : 0;

        question_engine::save_questions_usage_by_activity($quba);

        if ($this->session->status == self::STATUS_ASKED) {
            $this->session->status = self::STATUS_ANSWERED;
            $this->update();
            $this->calculate_model();
        }
    }

    protected function calculate_model()
    {
        if ($this->token) {
            $options = array(
                'http' => array(
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n"
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents('http://host.docker.internal:' . $this->port . '/api/calculate_model/'.$this->session->id, false, $context);
            var_dump($result);
        }
        return "";
    }

    public function finish() {
        $this->session->time_finished = date("Y-m-d H:m:s", time());
        $this->session->status = self::STATUS_FINISHED;
        $this->update();
        $this->calculate_model();
    }

    /**
     * @return bool
     */
    public function check_ending_condition()
    {
        return !is_null($this->session->slot) && $this->session->slot >= $this->tqg->__get("questions");
    }


    /**
     *
     * @return string
     */
    public function render_report() {
        $quba = question_engine::load_questions_usage_by_activity($this->questions_usage);
        $slots = $quba->get_slots();
        $table = new html_table();
        $table->head = array(get_string('questionname', 'tqg'), get_string('result', 'tqg'));
        foreach ($slots as $slot) {
            $at = $quba->get_question_attempt($slot);
            $table->data[] = array($at->get_question()->name, ($at->get_mark() > 0.7) ? 'correct' : 'wrong');
        }
        return html_writer::table($table);
    }

}