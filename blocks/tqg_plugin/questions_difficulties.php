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
$category_id = optional_param('category_id', null, PARAM_INT);
$context = \context_course::instance($course->id);

require_capability('mod/quiz:manage', $context);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/tqg_plugin/questions_difficulties.php');
$PAGE->set_title(get_string('questions_difficulties', 'block_tqg_plugin'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('tri_questionnaire_generator', 'block_tqg_plugin'));
$PAGE->navbar->add(get_string('questions_difficulties', 'block_tqg_plugin'));

if (optional_param('submit', 0, PARAM_BOOL)) {
    $questions_id = required_param_array('questions', PARAM_TEXT);
    $questions_difficulties = required_param_array('questions_difficulties', PARAM_TEXT);

    $token = $DB->get_record('tqg_login', array('user_email' => $email));

    if($token) {
        $question_difficulty = array();
        foreach ($questions_id as $index => $question_id) {
            $difficulty = 0;
            if($questions_difficulties[$index] == "easy") {
                $difficulty = -3;
            }
            else if($questions_difficulties[$index] == "medium") {
                $difficulty = 0;
            }
            else if($questions_difficulties[$index] == "hard") {
                $difficulty = 3;
            }
            else {
                continue;
            }
            $question_difficulty[] = array('id' => $question_id, 'ability' => $difficulty);
        }

        $options = array(
            'http' => array(
                'method' => 'PUT',
                'content' => json_encode($question_difficulty),
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer " . $token->user_token . "\r\n"
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents('http://host.docker.internal:' . $port . '/api/questions', false, $context);
        $response = json_decode($result);
    }
}

echo $OUTPUT->header();

echo '
  <table><tr><td>
  <h2>'.get_string('tqg_questions_difficulties', 'block_tqg_plugin').'</h2>
  <form action="questions_difficulties.php" method="post">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <input type="hidden" name="email" value="' . $email . '"/>
    <input type="hidden" name="hostname" value="' . $hostname . '"/>
    <input type="hidden" name="port" value="' . $port . '"/>
    <input type="hidden" name="import" value="1"/>
    <table class="generaltable">
    <tr>
        <td class="cell">
          '.get_string("name", "block_tqg_plugin").'
        </td>
        <td class="cell">
          '.get_string("easy", "block_tqg_plugin").'
        </td>
        <td class="cell">
          '.get_string("medium", "block_tqg_plugin").'
        </td>
        <td class="cell">
          '.get_string("hard", "block_tqg_plugin").'
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
    $result = file_get_contents('http://host.docker.internal:' . $port . '/api/questions', false, $context);
    $response = json_decode($result);

    $i = 0;
    foreach ($response->data as $question) {
        echo '
      <tr>
        <input type="hidden" name="questions['.$i.']" value="' . $question->id . '"/>
        <td class="cell">' . $question->name . '</td>
        <td class="cell">
          <input type="radio" name="questions_difficulties['.$i.']" value="easy"/>
        </td>
        <td class="cell">
          <input type="radio" name="questions_difficulties['.$i.']" value="medium"/>
        </td>
        <td class="cell">
          <input type="radio" name="questions_difficulties['.$i.']" value="hard"/>
        </td>
      </tr>';
        $i++;
    }
}
echo '
    </table>
    <input type="submit" name="submit" value="' . get_string('submit', 'block_tqg_plugin') . '"/>
  </form></td></tr></table>';

echo '
  <p><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course_id . '">' . get_string('return_to_course', 'block_tqg_plugin') . '</a></p>';
echo $OUTPUT->footer();