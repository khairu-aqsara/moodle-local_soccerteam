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

/**
 * Tests for Moodle Soccerteam
 *
 * @package    local_soccerteam
 * @category   test
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {

    /**
     * Test that the plugin is installed correctly
     *
     * @covers ::local_soccerteam_extend_navigation_course
     */
    public function test_plugin_installed(): void {
        $this->assertNotEmpty(get_config('local_soccerteam', 'version'));
    }

    /**
     * Test the navigation extension when the user has the required capability
     *
     * @covers ::local_soccerteam_extend_navigation_course
     */
    public function test_navigation_extension_with_capability(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create a user with the capability to view the soccer team.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/soccerteam:view', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $user->id, $context);
        accesslib_clear_all_caches_for_unit_testing();

        // Log in as the user.
        $this->setUser($user);

        // Setup the $PAGE global properly.
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        // Create a navigation object for testing.
        $navigation = new \navigation_node(['text' => $course->shortname,
                                         'shorttext' => $course->shortname,
                                         'type' => \navigation_node::TYPE_SETTING,
                                         'key' => $course->id]);

        // Call the function under test.
        local_soccerteam_extend_navigation_course($navigation, $course, $context);

        // Check that the node was added.
        $node = $navigation->find('soccerteam', \navigation_node::TYPE_SETTING);
        $this->assertNotEmpty($node);
        $this->assertEquals(get_string('pluginname', 'local_soccerteam'), $node->text);

        // Check URL is correct.
        $expectedurl = new \moodle_url('/local/soccerteam/index.php', ['id' => $course->id]);
        $this->assertEquals($expectedurl->out(), $node->action->out());
    }

    /**
     * Test navigation extension when the user doesn't have the capability
     *
     * @covers ::local_soccerteam_extend_navigation_course
     */
    public function test_navigation_extension_without_capability(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create a user without the capability to view the soccer team.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Explicitly prohibit the view capability.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/soccerteam:view', CAP_PROHIBIT, $roleid, $context);
        role_assign($roleid, $user->id, $context);
        accesslib_clear_all_caches_for_unit_testing();

        // Log in as the user.
        $this->setUser($user);

        // Setup the $PAGE global properly.
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        // Create a navigation object for testing.
        $navigation = new \navigation_node(['text' => $course->shortname,
                                         'shorttext' => $course->shortname,
                                         'type' => \navigation_node::TYPE_COURSE,
                                         'key' => $course->id]);

        // Call the function under test.
        local_soccerteam_extend_navigation_course($navigation, $course, $context);

        // Check that the node was not added - there should be no children.
        $this->assertEquals(0, count($navigation->children));
    }
}
