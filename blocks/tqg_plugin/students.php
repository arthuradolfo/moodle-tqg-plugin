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
    $students_id = required_param_array('students', PARAM_INT);

    $students = array();
    $student_grades_aux = array();
    foreach ($students_id as $student_id)
    {
        $student = $DB->get_record('user', array('id' => $student_id));
        $student_aux = array();
        $student_aux['moodle_id'] = $student->id;
        $student_aux['username'] = $student->username;
        $student_aux['email'] = $student->email;
        $student_aux['firstname'] = $student->firstname;
        $student_aux['lastname'] = $student->lastname;
        $student_aux['idnumber'] = $student->idnumber;
        $student_aux['institution'] = $student->institution;
        $student_aux['department'] = $student->department;
        $student_aux['phone1'] = $student->phone1;
        $student_aux['phone2'] = $student->phone2;
        $student_aux['city'] = $student->city;
        $student_aux['url'] = $student->url;
        $student_aux['icq'] = $student->icq;
        $student_aux['skype'] = $student->skype;
        $student_aux['aim'] = $student->aim;
        $student_aux['yahoo'] = $student->yahoo;
        $student_aux['msn'] = $student->msn;
        $student_aux['country'] = $student->country;
        $students[] = $student_aux;

        $student_grades = $DB->get_records_sql('SELECT qa.questionid, (qa.maxmark*qas.fraction) grade, qas.userid
                                                  FROM {question_attempts} qa
                                                  INNER JOIN {question_attempt_steps} qas
                                                  ON qas.questionattemptid = qa.id AND qas.userid = '.$student->id);
        foreach($student_grades as $student_grade)
        {
            $student_grade_aux = array();
            $student_grade_aux['student_moodle_id'] = $student_grade->userid;
            $student_grade_aux['question_moodle_id'] = $student_grade->questionid;
            $student_grade_aux['grade'] = $student_grade->grade;
            $student_grades_aux[] = $student_grade_aux;
        }
    }

    $token = $DB->get_record('tqg_login', array('user_email' => $email));

    if($token) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $students ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        var_dump(json_encode( $students ));
        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/students', false, $context );
        $response = json_decode( $result );

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode( $student_grades_aux ),
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: Bearer ". $token->user_token ."\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'http://host.docker.internal:'.$port.'/api/student_grades', false, $context );
        $response = json_decode( $result );

        var_dump($response);
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

echo '
  <table><tr><td>
  <h2>'.get_string('moodle_students', 'block_tqg_plugin').'</h2>';

$students = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname FROM {user} u
                                    LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                                    LEFT JOIN {enrol} e ON e.courseid = '. $course_id .' AND e.id = ue.id
                                    WHERE u.id = ue.userid');

    echo '
  <form action="students.php" method="post">
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

    foreach ($students as $student) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" name="students[]"  value="' . $student->id . '"/>
        </td>
        <td class="cell">' . $student->firstname . ' ' . $student->lastname . '</td>
      </tr>';
    }

echo '
    </table>
    <input type="submit" name="save" value="' . get_string('export', 'block_tqg_plugin') . '"/>
  </form></td>';

echo '<td>
  <h2>'.get_string('tqg_students', 'block_tqg_plugin').'</h2>
  <form action="students.php">
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
    $result = file_get_contents('http://host.docker.internal:' . $port . '/api/students', false, $context);
    $response = json_decode($result);

    foreach ($response->data as $student) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" name="questions[]" value="' . $student->id . '"/>
        </td>
        <td class="cell">' . $student->firstname . ' ' . $student->lastname . '</td>
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