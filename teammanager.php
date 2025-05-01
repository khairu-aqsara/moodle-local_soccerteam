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
 * Moodle Soccer team management page.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/soccerteam/lib.php');
require_once($CFG->dirroot . '/local/soccerteam/classes/form/team_form.php');

// Get parameters.
$courseid = required_param('courseid', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);  // 0 means create new, otherwise edit.
$deleteid = optional_param('delete', 0, PARAM_INT);  // User ID to delete.

// Get course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Set up page.
$PAGE->set_url('/local/soccerteam/teammanager.php', ['courseid' => $courseid, 'userid' => $userid]);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('manageteam', 'local_soccerteam'));
$PAGE->set_pagelayout('incourse');
$PAGE->set_secondary_active_tab('soccerteam');

// Check permissions.
require_login($course);
require_capability('local/soccerteam:manage', $context);

// Handle deletion if requested.
if ($deleteid && confirm_sesskey()) {
    $DB->delete_records('local_soccerteam_assignments', ['courseid' => $courseid, 'userid' => $deleteid]);
    redirect(
        new moodle_url('/local/soccerteam/teamoverview.php', ['id' => $courseid]),
        get_string('playerdeleted', 'local_soccerteam'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Set up form.
$formparams = ['courseid' => $courseid];
$isediting = false;
if ($userid) {
    $existing = $DB->get_record('local_soccerteam_assignments',
                                ['courseid' => $courseid, 'userid' => $userid],
                                '*', MUST_EXIST);
    $formparams['editing'] = true;
    $formparams['userid'] = $userid;
    $formparams['assignment'] = $existing;
    $isediting = true;
}

// Create the form with the correct action URL to preserve parameters.
$actionurl = new moodle_url('/local/soccerteam/teammanager.php', ['courseid' => $courseid]);
if ($userid) {
    $actionurl->param('userid', $userid);
}
$form = new \local_soccerteam\form\team_form($actionurl, $formparams);

// Form processing.
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/soccerteam/teamoverview.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {
    // Prepare data record.
    $record = new stdClass();
    $record->courseid = $courseid;
    $record->userid = $data->studentid;
    $record->position = $data->position;
    $record->jerseynumber = $data->jerseynumber;
    $record->timemodified = time();

    // Save to database.
    if ($isediting) {
        // This is an update.
        $record->id = $existing->id;
        $DB->update_record('local_soccerteam_assignments', $record);
        $message = get_string('playerupdated', 'local_soccerteam');
    } else {
        // This is a new record.
        $DB->insert_record('local_soccerteam_assignments', $record);
        $message = get_string('playeradded', 'local_soccerteam');
    }

    // Redirect back to team overview.
    redirect(
        new moodle_url('/local/soccerteam/teamoverview.php', ['id' => $courseid]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output header.
echo $OUTPUT->header();

// Display heading.
echo $OUTPUT->heading(get_string('manageteam', 'local_soccerteam'));

// Display form.
$form->display();

// Add back button.
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/soccerteam/teamoverview.php', ['id' => $courseid]),
        get_string('backtooverview', 'local_soccerteam'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

// Output footer.
echo $OUTPUT->footer();
