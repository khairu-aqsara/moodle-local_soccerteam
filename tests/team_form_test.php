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

namespace local_soccerteam;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/soccerteam/classes/form/team_form.php');

/**
 * Tests for team form functionality
 *
 * @package    local_soccerteam
 * @category   test
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_soccerteam\form\team_form
 */
class team_form_test extends \advanced_testcase {

    /**
     * Set up function for tests
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Create test data for the team
     *
     * @return array containing course, students, and team assignment
     */
    protected function create_test_data() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create students.
        $student1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $student2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);

        // Enrol students in the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');

        // Create team assignment for student1.
        $assignment = [
            'courseid' => $course->id,
            'userid' => $student1->id,
            'position' => 'goalkeeper',
            'jerseynumber' => 1,
            'timemodified' => time(),
        ];

        $assignmentid = $DB->insert_record('local_soccerteam_assignments', (object)$assignment);
        $assignment['id'] = $assignmentid;

        return [
            'course' => $course,
            'context' => $context,
            'students' => [
                'assigned' => $student1,
                'unassigned' => $student2,
            ],
            'assignment' => (object)$assignment,
        ];
    }

    /**
     * Test form validation for new player
     *
     * @covers \local_soccerteam\form\team_form::validation
     */
    public function test_form_validation_new_player(): void {
        global $DB;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];
        $student1 = $testdata['students']['assigned'];
        $student2 = $testdata['students']['unassigned'];

        // Create a minimal action URL for the form.
        $actionurl = new \moodle_url('/local/soccerteam/teammanager.php', ['courseid' => $course->id]);

        // Create the form with basic parameters (new player).
        $formparams = ['courseid' => $course->id];
        $form = new \local_soccerteam\form\team_form($actionurl->out(), $formparams);

        // Test validation when student is already on team.
        $submitteddata = [
            'studentid' => $student1->id,
            'position' => 'defender',
            'jerseynumber' => 5,
            'courseid' => $course->id,
        ];

        $errors = $form->validation($submitteddata, []);
        $this->assertArrayHasKey('studentid', $errors);
        $this->assertEquals(get_string('studentalreadyinteam', 'local_soccerteam'), $errors['studentid']);

        // Test validation when jersey number is already used.
        $submitteddata = [
            'studentid' => $student2->id,
            'position' => 'defender',
            'jerseynumber' => 1, // Already used by student1.
            'courseid' => $course->id,
        ];

        $errors = $form->validation($submitteddata, []);
        $this->assertArrayHasKey('jerseynumber', $errors);
        $this->assertEquals(get_string('duplicatejersey', 'local_soccerteam'), $errors['jerseynumber']);

        // Test validation with valid data.
        $submitteddata = [
            'studentid' => $student2->id,
            'position' => 'defender',
            'jerseynumber' => 5,
            'courseid' => $course->id,
        ];

        $errors = $form->validation($submitteddata, []);
        $this->assertEmpty($errors);
    }

    /**
     * Test form validation for editing existing player
     *
     * @covers \local_soccerteam\form\team_form::validation
     */
    public function test_form_validation_edit_player(): void {
        global $DB;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];
        $student1 = $testdata['students']['assigned'];
        $student2 = $testdata['students']['unassigned'];
        $assignment = $testdata['assignment'];

        // Create another team member to test number conflicts.
        $newassignment = [
            'courseid' => $course->id,
            'userid' => $student2->id,
            'position' => 'defender',
            'jerseynumber' => 5,
            'timemodified' => time(),
        ];
        $DB->insert_record('local_soccerteam_assignments', (object)$newassignment);

        // Create a minimal action URL for the form.
        $actionurl = new \moodle_url('/local/soccerteam/teammanager.php', ['courseid' => $course->id, 'userid' => $student1->id]);

        // Create the form for editing.
        $formparams = [
            'courseid' => $course->id,
            'editing' => true,
            'userid' => $student1->id,
            'assignment' => $assignment,
        ];
        $form = new \local_soccerteam\form\team_form($actionurl->out(), $formparams);

        // Test validation when trying to use another player's jersey number.
        $submitteddata = [
            'studentid' => $student1->id,
            'position' => 'goalkeeper',
            'jerseynumber' => 5, // Used by student2.
            'courseid' => $course->id,
            'isediting' => true,
        ];

        $errors = $form->validation($submitteddata, []);
        $this->assertArrayHasKey('jerseynumber', $errors);
        $this->assertEquals(get_string('duplicatejersey', 'local_soccerteam'), $errors['jerseynumber']);

        // Test validation with valid data (keeping same jersey number).
        $submitteddata = [
            'studentid' => $student1->id,
            'position' => 'midfielder', // Changed position.
            'jerseynumber' => 1, // Same jersey.
            'courseid' => $course->id,
            'isediting' => true,
        ];

        $errors = $form->validation($submitteddata, []);
        $this->assertEmpty($errors);
    }
}
