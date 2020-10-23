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
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
$category_id = optional_param('category_id', 0, PARAM_TEXT);
$context = \context_course::instance($course->id);

require_capability('mod/quiz:manage', $context);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/tqg_plugin/questions.php');
$PAGE->set_title(get_string('questions', 'block_tqg_plugin'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('tri_questionnaire_generator', 'block_tqg_plugin'));
$PAGE->navbar->add(get_string('questions', 'block_tqg_plugin'));

$questions = $DB->get_records('question', array('category' => $category_id));

if (optional_param('export', 0, PARAM_BOOL)) {
    $questions = $DB->get_records('question', array('category' => $category_id));

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=questions.csv');
    echo implode(',',
            array(
                'ID',
                get_string('name'),
                get_string('questions', 'block_tqg_plugin')
            )) . "\n";
    foreach ($questions as $question) {
        echo implode(',',
                array(
                    $question->id,
                    $question->name,
                    $def
                )) . "\n";
    }
    exit();

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
echo '
  <form action="questions.php">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <select name="category_id" onchange="this.form.submit()">
      <option value="0">' . get_string('select') . '</option>';
foreach ($opts as $optgroup) {
    foreach ($optgroup as $id => $category) {
        if ($id == $category_id) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        echo '<option value="' . $id . '"' . $selected . '>' . $category . '</option>';
    }
}
echo '
    </select>
  </form>';

if ($category_id) {
    $questions = $DB->get_records('question', array('category' => $category_id));
}
else {
    $questions = $DB->get_records('question');
}

    echo '
  <form action="questions.php" method="post">
    <input type="hidden" name="course_id" value="' . $course_id . '"/>
    <input type="hidden" name="category_id" value="' . $category_id . '"/>
    <table class="generaltable">
      <tr>
        <td class="cell">
          <input type="checkbox" onclick="cat_checkall(this)"/>
        </td>
        <td class="cell">
          <input type="button" value="' . get_string('copy') . '"
            onclick="cat_copy_data(\'diff_\')"/>
        </td>
      </tr>';

    foreach ($questions as $question) {
        echo '
      <tr>
        <td class="cell">
          <input type="checkbox" id="chk_' . $question->id . '"/>
        </td>
        <td class="cell">' . $question->name . '</td>
      </tr>';
    }
    echo '
    </table>
    <input type="submit" name="save" value="' . get_string('savechanges') . '"/>
  </form>';

    echo $OUTPUT->box_start() . $OUTPUT->single_button(
            new moodle_url('questions.php',
                array(
                    'export' => '1',
                    'course_id' => $course_id,
                    'category_id' => $category_id
                )), 'Export')
        . '<div class="center">
                   <form action="questions.php" method="post" enctype="multipart/form-data">
                   <input type="hidden" name="import" value="1"/>
                   <input type="hidden" name="course_id" value="' . $course_id . '"/>
                   <input type="hidden" name="qcat" value="'.($category_id).'"/>
                   <input type="file" name="file"/>
                   <input type="submit" value="Import"/>
                   </form>
                   </div>' . $OUTPUT->box_end();


echo '
  <p><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course_id . '">' . get_string('return_to_course', 'block_tqg_plugin') . '</a></p>';
echo $OUTPUT->footer();