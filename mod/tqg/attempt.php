<?php
require_once '../../config.php';
require_once $CFG->dirroot.'/mod/tqg/locallib.php';

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('tqg', $cmid);
$tqg = new tqg($cm);

require_login($cm->course, true, $cm);

if ($sid = optional_param('session', null, PARAM_TEXT)) {
    var_dump($sid);
    $session = new tqg_session($sid, $tqg->token, $tqg->port);
} else {
    $session_record = $tqg->create_session($cm->instance);
    $session = new tqg_session($session_record->id, $tqg->token, $tqg->port);
}

$url = new moodle_url('/mod/tqg/view.php', array('id' => $cmid));
$PAGE->set_url($url);
$PAGE->set_title($cm->name);
$PAGE->set_heading($cm->name);;

echo $OUTPUT->header();

echo $OUTPUT->heading($cm->name);

if ($session->check_ending_condition()) {
    var_dump("The end");
    $session->process_session();
    $session->finish();
}

if ($session->session->status >= tqg_session::STATUS_FINISHED) {
    echo "The end.";
} else {
    if ($session->session->status == tqg_session::STATUS_ASKED) {
        var_dump("The begining");
        if (optional_param('next', 0, PARAM_BOOL)) {
            $session->process_session();
        }
    }
    if ($session->session->status != tqg_session::STATUS_ASKED) {
        var_dump("The new question");
        $question = $session->get_next_question();

        if (!$question)
            throw new \moodle_exception('noquestionavailable', "TQG");
        echo $session->render_question($question->data->id);
    } else if ($session->current_question) {
        echo $session->render_question($session->current_question);
    }
}

echo $OUTPUT->footer();