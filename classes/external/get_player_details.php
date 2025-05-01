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
 * Class get_player_details
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_player_details extends \external_api {
    /**
     * Returns description of get_player_details parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_player_detail_parameters() {
        return new \external_function_parameters(
            [
                'courseid' => new \external_value(PARAM_INT, 'Course ID'),
                'userid' => new \external_value(PARAM_INT, 'User ID'),
            ]
        );
    }

    /**
     * Returns player details for a specific student in a course.
     *
     * @param int $courseid The course ID
     * @param int $userid The user ID
     * @return array player details
     * @throws \moodle_exception
     */
    public static function get_player_detail($courseid, $userid) {
        global $DB;

        // Parameter validation.
        $params = self::validate_parameters(
            self::get_player_detail_parameters(),
            ['courseid' => $courseid, 'userid' => $userid]
        );

        // Context validation.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Capability check.
        require_capability('local/soccerteam:view', $context);

        // Fetch player data.
        $playerdata = $DB->get_record(
            'local_soccerteam_assignments',
            ['courseid' => $params['courseid'], 'userid' => $params['userid']]
        );

        if (!$playerdata) {
            throw new \moodle_exception('playernotfound', 'local_soccerteam');
        }

        $user = $DB->get_record('user', ['id' => $playerdata->userid], 'id, firstname, lastname, email');
        if (!$user) {
            throw new \moodle_exception('usernotfound', 'local_soccerteam');
        }

        return [
            'userid' => $playerdata->userid,
            'fullname' => fullname($user),
            'email' => $user->email,
            'position' => $playerdata->position,
            'jerseynumber' => $playerdata->jerseynumber,
            'timemodified' => $playerdata->timemodified,
        ];
    }

    /**
     * Returns description of get_player_details returns.
     *
     * @return \external_single_structure
     */
    public static function get_player_detail_returns() {
        return new \external_single_structure(
            [
                'userid' => new \external_value(PARAM_INT, 'User ID'),
                'fullname' => new \external_value(PARAM_TEXT, 'User full name'),
                'email' => new \external_value(PARAM_TEXT, 'User email'),
                'position' => new \external_value(PARAM_TEXT, 'Player position'),
                'jerseynumber' => new \external_value(PARAM_INT, 'Jersey number'),
                'timemodified' => new \external_value(PARAM_INT, 'Last modified timestamp'),
            ]
        );
    }
}
