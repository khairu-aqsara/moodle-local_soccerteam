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
 * Renderer for the Soccer team plugin.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_soccerteam\output;

/**
 * Renderer for the soccer team plugin.
 *
 * @package    local_soccerteam
 * @copyright  2025 Khairu Aqsara <wenkhairu@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Renders the team overview.
     *
     * @param \local_soccerteam\output\team_overview $overview The team overview renderable.
     * @return string HTML content.
     */
    public function render_team_overview(\local_soccerteam\output\team_overview $overview) {
        $data = $overview->export_for_template($this);
        return $this->render_from_template('local_soccerteam/team_overview', $data);
    }
}
