<?php
/**
 * Computer-Adaptive Testing
 *
 * @package ucat
 * @author  VERSION2 Inc.
 * @version $Id: questions.php 35 2013-10-22 03:44:24Z yama $
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require(__DIR__.'/../../config.php');

$course_id = required_param('course_id', PARAM_INT);
$email = required_param('email', PARAM_TEXT);
$hostname = required_param('hostname', PARAM_TEXT);
$port = required_param('port', PARAM_INT);
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
$category_id = optional_param('category_id', null, PARAM_INT);
$context = \context_course::instance($course->id);

require_capability('mod/quiz:manage', $context);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/tqg_plugin/categories.php');
$PAGE->set_title(get_string('categories', 'block_tqg_plugin'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('tri_questionnaire_generator', 'block_tqg_plugin'));
$PAGE->navbar->add(get_string('categories', 'block_tqg_plugin'));
$PAGE->requires->js('/blocks/tqg_plugin/js/tqg_utilities.js');

if (optional_param('export', 0, PARAM_BOOL)) {
    $categories_id = required_param_array('categories', PARAM_INT);

    $categories = array();
    $questions = array();
    $answers = array();
    foreach ($categories_id as $category_id)
    {
        $category = $DB->get_record('question_categories', array('id' => $category_id));
        $category_aux = array();
        $category_aux['moodle_id'] = $category->id;
        $category_aux['name'] = $category->name;
        $category_aux['info'] = $category->info;
        $category_aux['info_format'] = $category->infoformat;
        $parent_category = $DB->get_record('question_categories', array('id' => $category->parent));
        if($parent_category->name != 'top') {
            $category_aux['category_moodle_id'] = $category->parent;
        }
        $categories[] = $category_aux;

        $questions_records = $DB->get_records('question', array('category' => $category->id));
        foreach ($questions_records as $question)
        {
            $question_aux = array();
            $question_aux['moodle_id'] = $question->id;
            $question_aux['category_moodle_id'] = $question->category;
            $question_aux['type'] = $question->qtype;
            $question_aux['name'] = $question->name;
            $question_aux['questiontext'] = $question->questiontext;
            $question_aux['questiontext_format'] = $question->questiontextformat;
            $question_aux['generalfeedback'] = $question->generalfeedback;
            $question_aux['generalfeedback_format'] = $question->generalfeedbackformat;
            $question_aux['penalty'] = $question->penalty;
            $question_aux['hidden'] = $question->hidden;
            $question_aux['idnumber'] = $question->idnumber;
            $questions[] = $question_aux;

            $answers_records = $DB->get_records('question_answers', array('question' => $question->id));
            foreach ($answers_records as $answer)
            {
                $answer_aux = array();
                $answer_aux['moodle_id'] = $answer->id;
                $answer_aux['question_moodle_id'] = $answer->question;
                $answer_aux['text'] = $answer->answer;
                $answer_aux['format'] = $answer->answerformat;
                $answer_aux['fraction'] = $answer->fraction;
                $answer_aux['feedback'] = $answer->feedback;
                $answer_aux['feedback_format'] = $answer->feedbackformat;
                $answers[] = $answer_aux;
            }
        }
    }

    $token = $DB->get_record('tqg_login', array('user_email' => $email));

    if($token) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $categories ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/categories', false, $context );
        $response = json_decode( $result );

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $questions ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/questions', false, $context );
        $response = json_decode( $result );

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $questions ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/questions', false, $context );
        $response = json_decode( $result );

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
echo '<table><tr><td>
  <h2>'.get_string('moodle_categories', 'block_tqg_plugin').'</h2>
  <form action="categories.php">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
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

foreach ($opts as $optgroup) {
    foreach ($optgroup as $id => $category) {
        echo '
          <tr>
            <td class="cell">
              <input type="checkbox" name="categories[]" value="'.$id.'"/>
            </td>
            <td class="cell">' . $category . '</td>
          </tr>';
    }
}
echo '
    </table>
    <input type="submit" name="save" value="' . get_string('export', 'block_tqg_plugin') . '"/>
  </form></td>';

echo '<td>
  <h2>'.get_string('tqg_categories', 'block_tqg_plugin').'</h2>
  <form action="categories.php">
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

if($token) {
    $options = array(
        'http' => array(
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n" .
                "Authorization: Bearer " . $token->user_token . "\r\n"
        )
    );

    $context = stream_context_create($options);
    $result = file_get_contents('http://host.docker.internal:' . $port . '/api/categories', false, $context);
    $response = json_decode($result);

    foreach ($response->data as $category) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" name="categories[]" value="' . $category->id . '"/>
        </td>
        <td class="cell">' . $category->name . '</td>
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