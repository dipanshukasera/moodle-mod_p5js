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
 * External service for saving p5js submissions.
 *
 * @package    mod_p5js
 * @copyright  2026 Dipanshu Kasera <dipanshukasera4034@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_p5js\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/p5js/lib.php');

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External API class for saving a user's submission.
 */
class save_submission extends external_api {
    /**
     * Returns the parameter definition for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'jscode' => new external_value(PARAM_RAW, 'The p5.js code to save'),
        ]);
    }

    /**
     * Saves a submission for the current user.
     *
     * @param int $cmid Course module id
     * @param string $jscode The submitted JavaScript code
     * @return array
     */
    public static function execute($cmid, $jscode) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'jscode' => $jscode,
        ]);

        $context = context_module::instance($params['cmid']);
        self::validate_context($context);

        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'p5js');
        $p5js = $DB->get_record('p5js', ['id' => $cm->instance], '*', MUST_EXIST);

        require_capability('mod/p5js:view', $context);

        p5js_save_submission($p5js->id, $USER->id, $params['jscode']);

        return [
            'status' => true,
            'message' => 'Saved successfully',
        ];
    }

    /**
     * Returns the structure of execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status of the operation'),
            'message' => new external_value(PARAM_TEXT, 'Message'),
        ]);
    }
}
