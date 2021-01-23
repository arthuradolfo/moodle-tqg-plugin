<?php
/**
 * Computer-Adaptive Testing
 *
 * @package ucat
 * @author  VERSION2 Inc.
 * @version $Id: questions.php 35 2013-10-22 03:44:24Z yama $
 */

require(__DIR__.'/../../config.php');

$course_id = required_param('course_id', PARAM_INT);
$email = required_param('email', PARAM_TEXT);
$hostname = required_param('hostname', PARAM_TEXT);
$port = required_param('port', PARAM_INT);
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
$question_id = optional_param('question_id', null, PARAM_INT);
$context = \context_course::instance($course->id);

require_capability('mod/quiz:manage', $context);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/tqg_plugin/questions.php');
$PAGE->set_title(get_string('questions', 'block_tqg_plugin'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('tri_questionnaire_generator', 'block_tqg_plugin'));
$PAGE->navbar->add(get_string('questions', 'block_tqg_plugin'));
$PAGE->requires->js('/blocks/tqg_plugin/js/tqg_utilities.js');

if (optional_param('export', 0, PARAM_BOOL)) {
    $answers_id = required_param_array('answers', PARAM_INT);

    $answers = array();
    foreach ($answers_id as $answer_id)
    {
        $answer = $DB->get_record('question_answers', array('id' => $answer_id));
        $answer_aux = array();
        $answer_aux['moodle_id'] = $answer->id;
        $answer_aux['question_moodle_id'] = $answer->question;
        $answer_aux['text'] = $answer->answer;
        $answer_aux['format'] = $answer->answerformat;
        $answer_aux['fraction'] = $answer->fraction;
        if(strlen($answer->feedback) >= 21477)
        {
            $answer_aux['feedback'] = "";
        }
        else
        {
            $answer_aux['feedback'] = $answer->feedback;
        }
        $answer_aux['feedback_format'] = $answer->feedbackformat;
        $answers[] = $answer_aux;
    }

    $token = $DB->get_record('tqg_login', array('user_email' => $email));

    if($token) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $answers ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/answers', false, $context );
        $response = json_decode( $result );
    }

} else if (optional_param('import', 0, PARAM_BOOL)) {
    $fp = fopen($_FILES['file']['tmp_name'], 'r');
    fgets($fp);
    while ($row = fgetcsv($fp)) {
        list($id, $name, $difficulty) = $row;

        if ($cquestion = $DB->get_record('question', array('id' => $id))) {
            $DB->update_record('question', $cquestion);
        } else {
            $cquestion = new stdClass();
            $cquestion->id = $id;
            $DB->insert_record('question', $cquestion);
        }
    }
    fclose($fp);
    redirect(new \moodle_url('/blocks/tqg_plugin/questions.php', [
        'course_id' => $course_id,
        'qcat' => $qcatid
    ]));
    redirect("$CFG->wwwroot/blocks/tqg_plugin/questions.php?course_id=$course_id");
}

echo $OUTPUT->header();


$contexts = new question_edit_contexts(context_course::instance($COURSE->id));
//         $mform->addElement('selectgroups', 'questioncategory', get_string('questioncategory', 'ucat'),
//                 question_category_options($contexts->having_cap('moodle/question:add')));
$opts = question_category_options($contexts->having_cap('moodle/question:add'));

$questions = array();
foreach ($opts as $optgroup) {
    foreach ($optgroup as $id => $category) {
        $id = intval($id);
        $questions = array_merge($DB->get_records_select('question',
            'category = '. $id .' AND (qtype="multichoice" OR qtype="truefalse")'
        ), $questions);
    }
}


echo '<form action="answers.php">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <input type="hidden" name="email" value="' . $email . '"/>
    <input type="hidden" name="hostname" value="' . $hostname . '"/>
    <input type="hidden" name="port" value="' . $port . '"/>
    <select name="question_id" onchange="this.form.submit()">
      <option value="0">' . get_string('select') . '</option>';
foreach ($questions as $question) {
    if ($question->id == $question_id) {
        $selected = ' selected="selected"';
    } else {
        $selected = '';
    }
    echo '<option value="' . $question->id . '"' . $selected . '>' . $question->name . '</option>';
}
echo '
    </select>
  </form>';

echo '
  <table><tr><td>
  <h2>'.get_string('moodle_answers', 'block_tqg_plugin').'</h2>';

$answers = array();
if ($question_id) {
    $answers = $DB->get_records('question_answers', ['question' => $question_id]);
}

    echo '
  <form action="questions.php" method="post">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <input type="hidden" name="question_id" value="' . $question_id . '"/>
    <input type="hidden" name="email" value="' . $email . '"/>
    <input type="hidden" name="hostname" value="' . $hostname . '"/>
    <input type="hidden" name="port" value="' . $port . '"/>
    <input type="hidden" name="export" value="1"/>
    <table class="generaltable">
      <tr>
        <td class="cell">
          <input type="checkbox" onclick="tqg_checkall(this)"/>
        </td>
        <td class="cell">
          '.get_string("name", "block_tqg_plugin").'
        </td>
      </tr>';

    foreach ($answers as $answer) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" name="answers[]"  value="' . $answer->id . '"/>
        </td>
        <td class="cell">' . $answer->answer . '</td>
      </tr>';
    }

echo '
    </table>
    <input type="submit" name="save" value="' . get_string('export', 'block_tqg_plugin') . '"/>
  </form></td>';

echo '<td>
  <h2>'.get_string('tqg_answers', 'block_tqg_plugin').'</h2>
  <form action="answers.php">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <input type="hidden" name="email" value="' . $email . '"/>
    <input type="hidden" name="hostname" value="' . $hostname . '"/>
    <input type="hidden" name="port" value="' . $port . '"/>
    <input type="hidden" name="import" value="1"/>
    <table class="generaltable">
      <tr>
        <td class="cell">
          <input type="checkbox" onclick="tqg_checkall(this)"/>
        </td>
        <td class="cell">
          '.get_string("name", "block_tqg_plugin").'
        </td>
      </tr>';

$token = $DB->get_record('tqg_login', array('user_email' => $email));

if($token && $question_id) {
    $options = array(
        'http' => array(
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n" .
                "Authorization: Bearer " . $token->user_token . "\r\n"
        )
    );

    $context = stream_context_create($options);
    $result = file_get_contents('http://host.docker.internal:' . $port . '/api/questions/?moodle_id='.$question_id, false, $context);
    $response = json_decode($result);

    foreach ($response->data->answers as $answer) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" name="questions[]" value="' . $answer->id . '"/>
        </td>
        <td class="cell">' . $answer->text . '</td>
      </tr>';
    }
}
echo '
    </table>
    <input type="submit" name="save" value="' . get_string('import', 'block_tqg_plugin') . '"/>
  </form></td></tr></table>';

echo '
  <p><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course_id . '">' . get_string('return_to_course', 'block_tqg_plugin') . '</a></p>';
echo $OUTPUT->footer();