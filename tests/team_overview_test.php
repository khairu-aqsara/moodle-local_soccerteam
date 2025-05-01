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

use core_renderer;

/**
 * Tests for team overview functionality
 *
 * @package    local_soccerteam
 * @category   test
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_soccerteam\output\team_overview
 */
class team_overview_test extends \advanced_testcase {

    /**
     * Set up function for tests
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Create test data for the team
     *
     * @return array containing course, students, and team assignments
     */
    protected function create_test_data() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create students.
        $student1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $student2 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Smith']);
        $student3 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Johnson']);
        $student4 = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Williams']);

        // Enrol students in the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student4->id, $course->id, 'student');

        // Create team assignments.
        $time = time();
        $assignments = [
            [
                'courseid' => $course->id,
                'userid' => $student1->id,
                'position' => 'goalkeeper',
                'jerseynumber' => 1,
                'timemodified' => $time,
            ],
            [
                'courseid' => $course->id,
                'userid' => $student2->id,
                'position' => 'defender',
                'jerseynumber' => 5,
                'timemodified' => $time,
            ],
            [
                'courseid' => $course->id,
                'userid' => $student3->id,
                'position' => 'midfielder',
                'jerseynumber' => 10,
                'timemodified' => $time,
            ],
            [
                'courseid' => $course->id,
                'userid' => $student4->id,
                'position' => 'forward',
                'jerseynumber' => 9,
                'timemodified' => $time,
            ],
        ];

        foreach ($assignments as $assignment) {
            $DB->insert_record('local_soccerteam_assignments', (object)$assignment);
        }

        return [
            'course' => $course,
            'context' => $context,
            'students' => [
                'goalkeeper' => $student1,
                'defender' => $student2,
                'midfielder' => $student3,
                'forward' => $student4,
            ],
            'assignments' => $assignments,
        ];
    }

    /**
     * Setup page environment for renderer tests
     *
     * @param \stdClass $course The course object
     * @return void
     */
    protected function setup_page_environment($course) {
        global $PAGE;

        // Set up the $PAGE global properly for the renderer.
        $context = \context_course::instance($course->id);
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url('/local/soccerteam/teamoverview.php', ['id' => $course->id]);
        $PAGE->set_title('Test Page');
        $PAGE->set_heading('Test Page Heading');
    }

    /**
     * Test the team_overview class constructor
     *
     * @covers \local_soccerteam\output\team_overview::__construct
     */
    public function test_team_overview_constructor(): void {
        global $DB;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];

        // Test with default parameters.
        $overview = new \local_soccerteam\output\team_overview($course->id);
        $this->assertInstanceOf('\local_soccerteam\output\team_overview', $overview);

        // Test with custom sorting parameters.
        $overview = new \local_soccerteam\output\team_overview($course->id, 'lastname', 'DESC');
        $this->assertInstanceOf('\local_soccerteam\output\team_overview', $overview);

        // Test with position filter.
        $overview = new \local_soccerteam\output\team_overview($course->id, 'jerseynumber', 'ASC', 'goalkeeper');
        $this->assertInstanceOf('\local_soccerteam\output\team_overview', $overview);
    }

    /**
     * Test the export_for_template method
     *
     * @covers \local_soccerteam\output\team_overview::export_for_template
     */
    public function test_team_overview_export_for_template(): void {
        global $PAGE, $CFG;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];

        // Create a user with the capability to manage the team.
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id);
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('local/soccerteam:manage', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $manager->id, $context);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($manager);

        // Set up the page environment properly.
        $this->setup_page_environment($course);

        // Create the renderer directly without using the $PAGE global.
        require_once($CFG->dirroot . '/local/soccerteam/classes/output/renderer.php');
        $renderer = new \local_soccerteam\output\renderer($PAGE, null);

        // Test the basic export.
        $overview = new \local_soccerteam\output\team_overview($course->id);
        $data = $overview->export_for_template($renderer);

        // Check basic structure.
        $this->assertEquals($course->id, $data->courseid);
        $this->assertTrue($data->has_members);
        $this->assertTrue($data->can_edit);

        // Check team members.
        $this->assertCount(4, $data->members);

        // Test with filtering.
        $overview = new \local_soccerteam\output\team_overview($course->id, 'jerseynumber', 'ASC', 'goalkeeper');
        $data = $overview->export_for_template($renderer);
        $this->assertEquals('goalkeeper', $data->position_filter);
        $this->assertCount(1, $data->members);
        $this->assertEquals('goalkeeper', $data->members[0]->position);

        // Test with different sorting.
        $overview = new \local_soccerteam\output\team_overview($course->id, 'lastname', 'DESC');
        $data = $overview->export_for_template($renderer);
        $this->assertEquals('lastname', $data->sort_by);
        $this->assertEquals('DESC', $data->sort_direction);
        $this->assertEquals('ASC', $data->sort_direction_inverse);
    }

    /**
     * Test permission checks in the team overview
     *
     * @covers \local_soccerteam\output\team_overview::export_for_template
     */
    public function test_team_overview_permissions(): void {
        global $PAGE, $CFG;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];

        // Create a user with view capability only.
        $viewer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($viewer->id, $course->id);
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('local/soccerteam:view', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $viewer->id, $context);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($viewer);

        // Set up the page environment properly.
        $this->setup_page_environment($course);

        // Create the renderer directly without using the $PAGE global.
        require_once($CFG->dirroot . '/local/soccerteam/classes/output/renderer.php');
        $renderer = new \local_soccerteam\output\renderer($PAGE, null);

        // Test the export with view-only permissions.
        $overview = new \local_soccerteam\output\team_overview($course->id);
        $data = $overview->export_for_template($renderer);

        // Viewer should see team but not have edit capability.
        $this->assertTrue($data->has_members);
        $this->assertFalse($data->can_edit);
    }
}
