<?php
/**
 * Construct an add module URL from the folderview add
 * activity form.  This is used when screen reader and the
 * like are used.
 *
 * @author Mark Nielsen
 */

require_once(dirname(dirname(dirname(__DIR__))).'/config.php');

$courseid = required_param('id', PARAM_INT);
$add      = required_param('add', PARAM_TEXT);
$section  = required_param('section', PARAM_INT);
$context  = get_context_instance(CONTEXT_COURSE, $courseid);

require_login($courseid, false, null, false, true);
require_capability('moodle/course:manageactivities', $context);
require_sesskey();

redirect(new moodle_url('/course/mod.php?add='.$add, array(
    'id'      => $courseid,
    'section' => $section,
    'sesskey' => sesskey(),
)));