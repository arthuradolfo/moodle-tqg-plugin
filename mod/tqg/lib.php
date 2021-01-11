<?php

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param stdClass $tqg
 * @return int
 */
function tqg_add_instance($tqg, $form) {
    global $DB;

    $tqg->id = $DB->insert_record('tqg', $tqg);

    return $tqg->id;
}

/**
 *
 * @param stdClass tqg
 * @return bool
 */
function tqg_update_instance($tqg, $form) {
    global $DB;

    $tqg->id = $tqg->instance;
    $DB->update_record('tqg', $tqg);

    return true;
}

/**
 *
 * @param int $id
 * @return bool
 */
function tqg_delete_instance($id) {
    global $DB;

    $DB->delete_records('tqg', array('id' => $id));

    return true;
}