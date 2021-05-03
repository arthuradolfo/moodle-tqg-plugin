<?php
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/course/moodleform_mod.php';
require_once $CFG->dirroot . '/mod/tqg/locallib.php';

class mod_tqg_mod_form extends moodleform_mod {
    public function definition() {
        global $DB, $COURSE, $PAGE;

        $f = $this->_form;

        $f->addElement('header', 'general', get_string('general', 'form'));

        $f->addElement('text', 'name', get_string('name'), array('size' => 64));
        if (!empty($CFG->formatstringstriptags)) {
            $f->setType('name', PARAM_TEXT);
        } else {
            $f->setType('name', PARAM_CLEANHTML);
        }
        $f->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor();

        $f->addElement('text', 'questions', get_string('numquestions', 'tqg'), array('size' => 4));
        $f->setType('questions', PARAM_INT);

        $contexts = new question_edit_contexts(context_course::instance($COURSE->id));
        $f->addElement('selectgroups', 'questioncategory', get_string('questioncategory', 'tqg'),
            question_category_options($contexts->having_cap('moodle/question:add')));

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}