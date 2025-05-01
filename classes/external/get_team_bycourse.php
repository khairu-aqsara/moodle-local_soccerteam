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

namespace local_soccerteam\external;

/**
 * Class get_team_bycourse
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_team_bycourse extends \external_api {
    /**
     * Returns description of get_team_by_course parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_team_by_course_parameters() {
        return new \external_function_parameters(
            [
                'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            ]
        );
    }

    /**
     * Returns the soccer team data for a course.
     *
     * @param int $courseid The course ID
     * @return array of team members
     * @throws \moodle_exception
     */
    public static function get_team_by_course($courseid) {
        global $DB;

        // Parameter validation.
        $params = self::validate_parameters(
            self::get_team_by_course_parameters(),
            ['courseid' => $courseid]
        );

        // Context validation.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Capability check.
        require_capability('local/soccerteam:view', $context);

        // Fetch team data.
        $teamdata = $DB->get_records('local_soccerteam_assignments', ['courseid' => $params['courseid']]);

        $result = [];

        // Prepare data for output.
        foreach ($teamdata as $member) {
            $user = $DB->get_record('user', ['id' => $member->userid], 'id, firstname, lastname');
            if (!$user) {
                // Skip if user not found.
                continue;
            }
            $result[] = [
                'userid' => $member->userid,
                'fullname' => fullname($user),
                'position' => $member->position,
                'jerseynumber' => $member->jerseynumber,
            ];
        }

        return $result;
    }

    /**
     * Returns description of get_team_by_course returns.
     *
     * @return \external_multiple_structure
     */
    public static function get_team_by_course_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                [
                    'userid' => new \external_value(PARAM_INT, 'User ID'),
                    'fullname' => new \external_value(PARAM_TEXT, 'User full name'),
                    'position' => new \external_value(PARAM_TEXT, 'Player position'),
                    'jerseynumber' => new \external_value(PARAM_INT, 'Jersey number'),
                ]
            )
        );
    }
}
