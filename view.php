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
 * View page for mod_p5js.
 *
 * @package    mod_p5js
 * @copyright  2026 Dipanshu Kasera <dipanshukasera4034@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'p5js');
$p5js = $DB->get_record('p5js', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/p5js:view', $context);

// Trigger viewing event.
$event = \mod_p5js\event\course_module_viewed::create([
    'objectid' => $p5js->id,
    'context' => $context,
]);
$event->add_record_snapshot('p5js', $p5js);
$event->trigger();

$PAGE->set_url('/mod/p5js/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($p5js->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

// Load user submission.
$submission = p5js_get_submission($p5js->id, $USER->id);

$defaultfiles = [
    'sketch.js' =>
        "function setup() {\n" .
        "  createCanvas(400, 400);\n" .
        "  background(255);\n" .
        "}\n\n" .
        "function draw() {\n" .
        "  circle(mouseX, mouseY, 80);\n" .
        "}",
    'index.html' =>
        "<!DOCTYPE html>\n" .
        "<html>\n" .
        "  <head>\n" .
        "    <script src=\"p5.js\"></script>\n" .
        "    <link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\">\n" .
        "    <meta charset=\"utf-8\" />\n" .
        "  </head>\n" .
        "  <body>\n" .
        "    <script src=\"sketch.js\"></script>\n" .
        "  </body>\n" .
        "</html>",
    'style.css' =>
        "html, body {\n" .
        "  margin: 0;\n" .
        "  padding: 0;\n" .
        "}\n" .
        "canvas {\n" .
        "  display: block;\n" .
        "}",
];

$jscodejson = $submission ? $submission->js_code : json_encode($defaultfiles);

// Render the template.
$templatecontext = (object) [
    'id' => $p5js->id,
    'cmid' => $cm->id,
    'name' => format_string($p5js->name),
    'intro' => format_module_intro('p5js', $p5js, $cm->id),
    'js_code_json' => json_encode($jscodejson),
];

echo $OUTPUT->render_from_template('mod_p5js/view_page', $templatecontext);

echo $OUTPUT->footer();
