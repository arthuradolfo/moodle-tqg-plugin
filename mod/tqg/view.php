<?php
require_once '../../config.php';
require_once $CFG->dirroot . '/mod/tqg/locallib.php';

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('tqg', $cmid);
$tqg = new tqg($cm);

require_login($cm->course, true, $cm);

$PAGE->set_url('/mod/tqg/view.php');
$PAGE->set_title($cm->name);
$PAGE->set_heading($cm->name);
$url = new moodle_url('/mod/tqg/view.php', array('id' => $cmid));

echo $OUTPUT->header();

echo $OUTPUT->heading($cm->name);

echo $OUTPUT->box_start()
    .format_text($tqg->intro, $tqg->introformat)
    .$OUTPUT->box_end();

echo $OUTPUT->single_button(
    new moodle_url('/mod/tqg/attempt.php', array('cmid' => $cm->id)),
    get_string('startattempt', 'tqg')
);

if ($tqg->is_manager()) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/tqg/reestimate.php', array('cmid' => $cm->id)),
        get_string('reestimatedifficulties', 'tqg')
    );
}

echo $OUTPUT->footer();