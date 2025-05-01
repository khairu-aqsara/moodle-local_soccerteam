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
 * Moodle Soccer team overview page.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/soccerteam/lib.php');

// Get parameters.
$courseid = required_param('id', PARAM_INT);
$sortby = optional_param('sortby', 'jerseynumber', PARAM_ALPHA);
$sortdirection = optional_param('sortdirection', 'ASC', PARAM_ALPHA);
$positionfilter = optional_param('position', '', PARAM_ALPHA);

// Get course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Set up page.
$PAGE->set_url('/local/soccerteam/teamoverview.php', [
    'id' => $courseid,
    'sortby' => $sortby,
    'sortdirection' => $sortdirection,
    'position' => $positionfilter,
]);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('teamoverview', 'local_soccerteam'));
$PAGE->set_pagelayout('incourse');
$PAGE->set_secondary_active_tab('soccerteam');

// Check permissions.
require_login($course);
require_capability('local/soccerteam:view', $context);

// Add CSS and JS.
$PAGE->requires->css('/local/soccerteam/styles.css');

// Output header.
echo $OUTPUT->header();

// Display team overview.
$overview = new \local_soccerteam\output\team_overview($courseid, $sortby, $sortdirection, $positionfilter);
echo $OUTPUT->render($overview);

// Output footer.
echo $OUTPUT->footer();
