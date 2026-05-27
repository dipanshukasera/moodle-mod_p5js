<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for mod_p5js.
 *
 * @package    mod_p5js
 * @copyright  2026 Dipanshu Kasera <dipanshukasera4034@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a new instance of p5js.
 *
 * @param stdClass $p5js An object from the form in mod_form.php
 * @return int The id of the newly inserted record
 */
function p5js_add_instance($p5js) {
    global $DB;

    $p5js->timemodified = time();
    $p5js->id = $DB->insert_record('p5js', $p5js);

    return $p5js->id;
}

/**
 * Updates an instance of p5js.
 *
 * @param stdClass $p5js An object from the form in mod_form.php
 * @return bool True if successful, false otherwise
 */
function p5js_update_instance($p5js) {
    global $DB;

    $p5js->timemodified = time();
    $p5js->id = $p5js->instance;

    return $DB->update_record('p5js', $p5js);
}

/**
 * Deletes an instance of p5js.
 *
 * @param int $id The id of the record to delete
 * @return bool True if successful, false otherwise
 */
function p5js_delete_instance($id) {
    global $DB;

    if (!$p5js = $DB->get_record('p5js', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('p5js_submissions', ['p5jsid' => $p5js->id]);
    $DB->delete_records('p5js', ['id' => $p5js->id]);

    return true;
}

/**
 * Get a user's submission for a p5js activity.
 *
 * @param int $p5jsid The p5js activity id
 * @param int $userid The user id
 * @return stdClass|false
 */
function p5js_get_submission($p5jsid, $userid) {
    global $DB;

    return $DB->get_record('p5js_submissions', ['p5jsid' => $p5jsid, 'userid' => $userid]);
}

/**
 * Save or update a user's submission.
 *
 * @param int $p5jsid The p5js activity id
 * @param int $userid The user id
 * @param string $jscode The submitted JavaScript code
 * @return int
 */
function p5js_save_submission($p5jsid, $userid, $jscode) {
    global $DB;

    $submission = p5js_get_submission($p5jsid, $userid);

    if ($submission) {
        $submission->js_code = $jscode;
        $submission->timemodified = time();
        $DB->update_record('p5js_submissions', $submission);

        return $submission->id;
    }

    $submission = new stdClass();
    $submission->p5jsid = $p5jsid;
    $submission->userid = $userid;
    $submission->js_code = $jscode;
    $submission->timemodified = time();

    return $DB->insert_record('p5js_submissions', $submission);
}

/**
 * Returns the list of features supported by the module.
 *
 * @param string $feature Feature name
 * @return bool|null
 */
function p5js_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;

        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}
