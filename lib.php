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
 * Library of functions for local_soccerteam
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extends navigation for the soccer team plugin.
 *
 * @param global_navigation $navigation The navigation object
 * @param stdClass $course The course object
 * @param context_course $context The course context
 * @return void
 */
function local_soccerteam_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/soccerteam:view', $context)) {
        $url = new moodle_url('/local/soccerteam/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_soccerteam'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'soccerteam',
            new pix_icon('i/groups', '')
        );
    }
}

