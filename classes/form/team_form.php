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
 * Form for adding/editing team members.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_soccerteam\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use moodleform;

/**
 * Team management form class.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class team_form extends moodleform {
    /** @var bool Are we editing an existing record? */
    private $isediting = false;

    /** @var object The existing assignment record if editing */
    private $assignment = null;

    /**
     * Define the form elements.
     */
    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form;
        $courseid = $COURSE->id;

        // Get editing state and existing assignment if editing.
        $this->isediting = !empty($this->_customdata['editing']);
        if ($this->isediting && !empty($this->_customdata['assignment'])) {
            $this->assignment = $this->_customdata['assignment'];
        }

        // Set form data if editing.
        if ($this->isediting) {
            $mform->setDefault('studentid', $this->assignment->userid);
            $mform->setDefault('position', $this->assignment->position);
            $mform->setDefault('jerseynumber', $this->assignment->jerseynumber);
        }

        $context = \context_course::instance($courseid);

        // Get enrolled students in the course.
        $students = get_enrolled_users($context, 'moodle/course:isincompletionreports', 0,
            'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename');
        $studentoptions = [];
        foreach ($students as $student) {
            $studentoptions[$student->id] = fullname($student);
        }

        // Student dropdown.
        $mform->addElement('select', 'studentid', get_string('selectstudent', 'local_soccerteam'), $studentoptions);
        $mform->addRule('studentid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('studentid', 'selectstudent', 'local_soccerteam');

        // Position dropdown.
        $positions = [
            'goalkeeper' => get_string('goalkeeper', 'local_soccerteam'),
            'defender' => get_string('defender', 'local_soccerteam'),
            'midfielder' => get_string('midfielder', 'local_soccerteam'),
            'forward' => get_string('forward', 'local_soccerteam'),
        ];
        $mform->addElement('select', 'position', get_string('position', 'local_soccerteam'), $positions);
        $mform->addRule('position', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('position', 'position', 'local_soccerteam');

        // Jersey number - using a select for numbers 1-25.
        $availablejerseynumbers = array_combine(range(1, 25), range(1, 25));
        $mform->addElement('select', 'jerseynumber', get_string('jerseynumber', 'local_soccerteam'), $availablejerseynumbers);
        $mform->setType('jerseynumber', PARAM_INT);
        $mform->addRule('jerseynumber', get_string('required'), 'required', null, 'client');
        $mform->addRule('jerseynumber', get_string('numbererror', 'local_soccerteam'), 'numeric', null, 'client');
        $mform->addRule('jerseynumber', get_string('numberrange', 'local_soccerteam'), 'regex', '/^([1-9]|[1-9][0-9])$/', 'client');
        $mform->addHelpButton('jerseynumber', 'jerseynumber', 'local_soccerteam');

        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);

        $mform->addElement('hidden', 'isediting', $this->isediting);
        $mform->setType('isediting', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('submit', 'local_soccerteam'));
    }

    /**
     * Validate form data.
     *
     * @param array $data Array of form data
     * @param array $files Array of files
     * @return array of errors
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Get context variables.
        $courseid = $data['courseid'];
        $studentid = $data['studentid'];
        $jerseynumber = $data['jerseynumber'];

        // Check for unique jersey number and student.
        if ($this->isediting) {
            $existing = $DB->get_record('local_soccerteam_assignments', ['courseid' => $courseid, 'userid' => $studentid]);
            if ($existing && $existing->jerseynumber != $jerseynumber) {
                $duplicate = $DB->get_record('local_soccerteam_assignments',
                                           ['courseid' => $courseid, 'jerseynumber' => $jerseynumber]);
                // Check if the jersey number is already assigned to another player.
                if ($duplicate) {
                    if ($duplicate->userid != $existing->userid) {
                        $errors['studentid'] = get_string('studentalreadyinteam', 'local_soccerteam');
                    }
                    $errors['jerseynumber'] = get_string('duplicatejersey', 'local_soccerteam');
                }
            }
        } else {
            // Check if the student is already in the team.
            if ($DB->record_exists('local_soccerteam_assignments', ['courseid' => $courseid, 'userid' => $studentid])) {
                $errors['studentid'] = get_string('studentalreadyinteam', 'local_soccerteam');
            }

            // Check for duplicate jersey number.
            if ($DB->record_exists('local_soccerteam_assignments', ['courseid' => $courseid, 'jerseynumber' => $jerseynumber])) {
                $errors['jerseynumber'] = get_string('duplicatejersey', 'local_soccerteam');
            }
        }

        return $errors;
    }
}
