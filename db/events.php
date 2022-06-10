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
 * Events in Plugin
 *
 * @package    local_leeloolxp_lct
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'includefile' => '/local/leeloolxp_lct/lib.php',
        'callback' => 'local_leeloolxp_lct_attempt_submitted',
        'internal' => false,
    ),

    array(
        'eventname' => '\mod_quiz\event\attempt_started',
        'includefile' => '/local/leeloolxp_lct/lib.php',
        'callback' => 'local_leeloolxp_lct_attempt_started',
        'internal' => false,
    ),

    array(
        'eventname' => '\mod_quiz\event\attempt_abandoned',
        'includefile' => '/local/leeloolxp_lct/lib.php',
        'callback' => 'local_leeloolxp_lct_attempt_abandoned',
        'internal' => false,
    ),
);
