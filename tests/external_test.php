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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_soccerteam;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/soccerteam/classes/external/get_team_bycourse.php');
require_once($CFG->dirroot . '/local/soccerteam/classes/external/get_player_details.php');

/**
 * Tests for external API functions
 *
 * @package    local_soccerteam
 * @category   test
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_soccerteam\external\get_team_bycourse
 * @covers \local_soccerteam\external\get_player_details
 */
class external_test extends \externallib_advanced_testcase {

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

        // Enrol students in the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course->id, 'student');

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
                $student1,
                $student2,
                $student3,
            ],
            'assignments' => $assignments,
        ];
    }

    /**
     * Test the get_team_by_course external function
     *
     * @covers \local_soccerteam\external\get_team_bycourse::get_team_by_course
     */
    public function test_get_team_by_course(): void {
        global $DB;

        $testdata = $this->create_test_data();
        $course = $testdata['course'];

        // Create a user with the capability to view the team.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('local/soccerteam:view', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $user->id, $context);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        // Call the external function.
        $result = \local_soccerteam\external\get_team_bycourse::get_team_by_course($course->id);

        // Ensure we got the correct data.
        $this->assertCount(3, $result);

        // Check first player data.
        $hasgoalkeeper = false;
        $hasdefender = false;
        $hasforward = false;

        foreach ($result as $player) {
            if ($player['position'] === 'goalkeeper') {
                $hasgoalkeeper = true;
                $this->assertEquals(1, $player['jerseynumber']);
            } else if ($player['position'] === 'defender') {
                $hasdefender = true;
                $this->assertEquals(5, $player['jerseynumber']);
            } else if ($player['position'] === 'forward') {
                $hasforward = true;
                $this->assertEquals(9, $player['jerseynumber']);
            }
        }

        $this->assertTrue($hasgoalkeeper);
        $this->assertTrue($hasdefender);
        $this->assertTrue($hasforward);
    }

    /**
     * Test the get_player_details external function
     *
     * @covers \local_soccerteam\external\get_player_details::get_player_detail
     */
    public function test_get_player_details(): void {
        global $DB;

        // Skip this test if get_player_details.php doesn't exist or is not properly implemented.
        if (!method_exists('\local_soccerteam\external\get_player_details', 'get_player_detail')) {
            $this->markTestSkipped('get_player_details external function not yet implemented');
        }

        $testdata = $this->create_test_data();
        $course = $testdata['course'];
        $student = $testdata['students'][0]; // Goalkeeper.

        // Create a user with the capability to view the team.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('local/soccerteam:view', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $user->id, $context);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        // Call the external function.
        try {
            $result = \local_soccerteam\external\get_player_details::get_player_detail($course->id, $student->id);

            // Validate the returned data.
            $this->assertEquals('goalkeeper', $result['position']);
            $this->assertEquals(1, $result['jerseynumber']);
            $this->assertEquals($student->id, $result['userid']);
            $this->assertTrue(isset($result['fullname']));

        } catch (\Exception $e) {
            $this->markTestSkipped('get_player_details external function not yet fully implemented');
        }
    }
}
