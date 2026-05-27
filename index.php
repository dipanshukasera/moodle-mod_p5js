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
 * Index page for mod_p5js.
 *
 * @package    mod_p5js
 * @copyright  2026 Dipanshu Kasera <dipanshukasera4034@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/p5js/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_p5js'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_p5js'));

if (!$p5jss = get_all_instances_in_course('p5js', $course)) {
    notice(
        get_string('nop5jsinstances', 'mod_p5js'),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$table = new html_table();
$table->head = [get_string('name'), get_string('intro', 'mod_p5js')];
$table->align = ['left', 'left'];

foreach ($p5jss as $p5js) {
    $url = new moodle_url('/mod/p5js/view.php', ['id' => $p5js->coursemodule]);
    $name = html_writer::link($url, format_string($p5js->name));
    $table->data[] = [$name, format_module_intro('p5js', $p5js, $p5js->coursemodule)];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
