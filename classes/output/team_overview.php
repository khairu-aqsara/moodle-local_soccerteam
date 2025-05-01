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

namespace local_soccerteam\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Team overview renderable class.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class team_overview implements renderable, templatable {

    /** @var int The course id */
    private $courseid;

    /** @var array The team members data */
    private $teamdata;

    /** @var string Field to sort by */
    private $sortby;

    /** @var string Sort direction (ASC or DESC) */
    private $sortdirection;

    /** @var string Position filter value */
    private $positionfilter;

    /**
     * Constructor for the team overview.
     *
     * @param int $courseid The course ID
     * @param string $sortby Field to sort by (jerseynumber, position, lastname)
     * @param string $sortdirection Direction to sort (ASC/DESC)
     * @param string $positionfilter Filter by position
     */
    public function __construct($courseid, $sortby = 'jerseynumber', $sortdirection = 'ASC', $positionfilter = '') {
        global $DB;

        $this->courseid = $courseid;
        $this->sortby = $sortby;
        $this->sortdirection = $sortdirection;
        $this->positionfilter = $positionfilter;

        // Build SQL query with filters and sorting.
        $params = ['courseid' => $courseid];
        $sql = "SELECT sa.*, u.firstname, u.lastname, u.email
                FROM {local_soccerteam_assignments} sa
                JOIN {user} u ON sa.userid = u.id
                WHERE sa.courseid = :courseid";

        if (!empty($positionfilter)) {
            $sql .= " AND sa.position = :position";
            $params['position'] = $positionfilter;
        }

        // Ensure sort field is valid.
        $validfields = ['jerseynumber', 'position', 'lastname'];
        if (!in_array($sortby, $validfields)) {
            $sortby = 'jerseynumber';
        }

        // Adjust sort field for user table fields.
        $sortfield = ($sortby == 'lastname') ? "u.lastname" : "sa.$sortby";

        // Ensure sort direction is valid.
        $sortdirection = ($sortdirection == 'DESC') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY $sortfield $sortdirection";

        $this->teamdata = $DB->get_records_sql($sql, $params);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output The renderer
     * @return stdClass Data for template rendering
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = new stdClass();
        $data->courseid = $this->courseid;
        $data->wwwroot = $CFG->wwwroot;
        $data->has_members = !empty($this->teamdata);
        $data->position_filter = $this->positionfilter;

        // Add capability check for the entire template.
        $data->can_edit = has_capability('local/soccerteam:manage',
                                       \context_course::instance($this->courseid));

        // Add sort information.
        $data->sort_by = $this->sortby;
        $data->sort_direction = $this->sortdirection;
        $data->sort_direction_inverse = ($this->sortdirection == 'ASC') ? 'DESC' : 'ASC';

        // Add sort column indicators.
        $data->sort_by_is_jerseynumber = ($this->sortby === 'jerseynumber');
        $data->sort_by_is_lastname = ($this->sortby === 'lastname');
        $data->sort_by_is_position = ($this->sortby === 'position');

        // Add sort direction indicators.
        $data->sort_direction_is_asc = ($this->sortdirection === 'ASC');
        $data->sort_direction_is_desc = ($this->sortdirection === 'DESC');

        // Add filter options.
        $data->positions = [
            [
                'value' => '',
                'name' => get_string('allpositions', 'local_soccerteam'),
                'selected' => empty($this->positionfilter),
            ],
            [
                'value' => 'goalkeeper',
                'name' => get_string('goalkeeper', 'local_soccerteam'),
                'selected' => $this->positionfilter === 'goalkeeper',
            ],
            [
                'value' => 'defender',
                'name' => get_string('defender', 'local_soccerteam'),
                'selected' => $this->positionfilter === 'defender',
            ],
            [
                'value' => 'midfielder',
                'name' => get_string('midfielder', 'local_soccerteam'),
                'selected' => $this->positionfilter === 'midfielder',
            ],
            [
                'value' => 'forward',
                'name' => get_string('forward', 'local_soccerteam'),
                'selected' => $this->positionfilter === 'forward',
            ],
        ];

        // Add team members.
        $data->members = [];
        foreach ($this->teamdata as $member) {
            $memberdata = new stdClass();
            $memberdata->userid = $member->userid;
            $memberdata->fullname = fullname($member);
            $memberdata->email = $member->email;
            $memberdata->position = $member->position;
            // Mdlcode-disable-next-line cannot-parse-string.
            $memberdata->position_localized = get_string($member->position, 'local_soccerteam');
            $memberdata->jerseynumber = $member->jerseynumber;
            $memberdata->sesskey = sesskey();

            $data->members[] = $memberdata;
        }

        return $data;
    }
}
