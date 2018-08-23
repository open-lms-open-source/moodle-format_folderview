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
 * Construct an add module URL from the folderview add
 * activity form.  This is used when screen reader and the
 * like are used.
 *
 * @package   format_folderview
 * @copyright Copyright (c) 2009 Blackboard Inc. (http://www.blackboardopenlms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__DIR__))).'/config.php');

$courseid = required_param('id', PARAM_INT);
$add      = optional_param('add', '', PARAM_TEXT);
$section  = required_param('section', PARAM_INT);
$context  = context_course::instance($courseid);

require_login($courseid, false, null, false, true);
require_capability('moodle/course:manageactivities', $context);
require_sesskey();

if (empty($add)) {
    print_error('mustselectresource', 'format_folderview', new moodle_url('/course/view.php', array('id' => $courseid)));
}
redirect(new moodle_url('/course/mod.php?add='.$add, array(
    'id'      => $courseid,
    'section' => $section,
    'sesskey' => sesskey(),
)));
