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
 * External functions and service declaration for Moodle Soccerteam
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_soccerteam
 * @category   webservice
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_soccerteam_get_team_by_course' => [
        'classname'     => 'local_soccerteam\external\get_team_bycourse',
        'methodname'    => 'get_team_by_course',
        'classpath'     => 'local/soccerteam/classes/external/get_team_bycourse.php',
        'description'   => 'Get soccer team data for a specific course',
        'type'          => 'read',
        'capabilities'  => 'local/soccerteam:view',
    ],
    'local_soccerteam_get_player_details' => [
        'classname'     => 'local_soccerteam\external\get_player_details',
        'methodname'    => 'get_player_detail',
        'classpath'     => 'local/soccerteam/classes/external/get_player_details.php',
        'description'   => 'Get details for a specific player in a course',
        'type'          => 'read',
        'capabilities'  => 'local/soccerteam:view',
    ],
];

// Define the service.
$services = [
    'Soccer Team API' => [
        'functions' => [
            'local_soccerteam_get_team_by_course',
            'local_soccerteam_get_player_details',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_soccerteam_api',
    ],
];
