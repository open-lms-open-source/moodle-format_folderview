<?php
/**
 * Folder View Course Format
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package format_folderview
 * @author David Mills
 */

/**
 * @method format_folderview_renderer get_renderer(moodle_page $page)
 */
class format_folderview extends format_base {
    public function get_course() {
        $course = parent::get_course();
        $course->coursedisplay = COURSE_DISPLAY_SINGLEPAGE;

        return $course;
    }

    public function uses_sections() {
        return true;
    }

    public function get_view_url($section, $options = array()) {
        if (!empty($options['navigation'])) {
            if (array_key_exists('sr', $options)) {
                $sectionno = $options['sr'];
            } else if (is_object($section)) {
                $sectionno = $section->section;
            } else {
                $sectionno = $section;
            }
            if ($sectionno !== null) {
                return new moodle_url('/course/view.php', array(
                    'id' => $this->get_courseid(),
                    'section' => $sectionno
                ));
            }
        }
        return parent::get_view_url($section, $options);
    }

    public function get_section_name($section) {
        $section = $this->get_section($section);

        if (!empty($section->name)) {
            return format_string($section->name, true, array('context' => context_course::instance($this->courseid)));
        } else if ($section->section == 0) {
            return get_string('section0name', 'format_folderview');
        }
        return get_string('sectionname', 'format_folderview').' '.$section->section;
    }

    public function supports_ajax() {
        $ajaxsupport                 = new stdClass();
        $ajaxsupport->capable        = true;
        $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
        return $ajaxsupport;
    }

    public function ajax_section_move() {
        global $PAGE;

        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;

        // if section is specified in course/view.php, make sure it is expanded in navigation
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)
            ) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        parent::extend_course_navigation($navigation, $node);
    }

    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseconfig        = get_config('moodlecourse');
            $courseformatoptions = array(
                'numsections'    => array(
                    'default' => $courseconfig->numsections,
                    'type'    => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type'    => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['numsections']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $max          = $courseconfig->maxsections;
            if (!isset($max) || !is_numeric($max)) {
                $max = 52;
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections'    => array(
                    'label'              => new lang_string('numberweeks'),
                    'element_type'       => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label'              => new lang_string('hiddensections'),
                    'help'               => 'hiddensections',
                    'help_component'     => 'moodle',
                    'element_type'       => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        if ($oldcourse !== null) {
            $data      = (array) $data;
            $oldcourse = (array) $oldcourse;
            $options   = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        // If previous format does not have the field 'numsections'
                        // and $data['numsections'] is not set,
                        // we fill it with the maximum section number from the DB
                        $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                            WHERE course = ?', array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default
                            $data['numsections'] = $maxsection;
                        }
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }
}

/**
 * Course deletion
 *
 * @param $courseid
 * @param bool $showfeedback
 */
function format_folderview_delete_course($courseid, $showfeedback = false) {
    global $DB, $OUTPUT;

    $DB->delete_records('format_folderview_display', array('course' => $courseid));
    $DB->delete_records('user_preferences', array('name' => "format_folderview_$courseid"));

    if ($showfeedback) {
        echo $OUTPUT->notification(get_string('deletecoursedone', 'format_folderview'), 'notifysuccess');
    }
}

/**
 * Copied from print_section_add_menus
 * Modified to return an associative array of all resources and activities
 * that are available to given user in given course for given section
 * As it is written, the section only affects the value of the addurl field
 */
function format_folderview_get_course_resource_types($course, $section, $modnames) {
    global $CFG;

    $sm = get_string_manager();
    //Setup Options for string text conversion
    $formatopt = new stdClass();
    $formatopt->trusted = false;
    $formatopt->noclean = false;
    $formatopt->smiley = false;
    $formatopt->filter = false;
    $formatopt->para = true;
    $formatopt->newlines = false;
    $formatopt->overflowdiv = false;

    // check to see if user can add menus
    if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
        return false;
    }

    $urlbase = "$CFG->wwwroot/course/mod.php?id=$course->id&section=$section&sesskey=".sesskey().'&add=';

    $resources = array();

    foreach($modnames as $modname=>$modnamestr) {
        if (!course_allowed_module($course, $modname)) {
            continue;
        }
        $libfile = "$CFG->dirroot/mod/$modname/lib.php";
        if (!file_exists($libfile)) {
            continue;
        }
        include_once($libfile);
        $gettypesfunc =  $modname.'_get_types';
        if (function_exists($gettypesfunc)) {
            // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
            if ($types = $gettypesfunc()) {
                $groupname = null;
                foreach($types as $type) {
                    $menu = new stdClass();
                    if ($type->typestr === '--') {
                        continue;
                    }
                    if (strpos($type->typestr, '--') === 0) {
                        $groupname = str_replace('--', '', $type->typestr);
                        continue;
                    }
                    $menu->modname = $modname;
                    $type->type = str_replace('&amp;', '&', $type->type);
                    $menu->type=$type->type;
                    $menu->archetype=$type->modclass;
                    $menu->isactivity = ($type->modclass != MOD_CLASS_RESOURCE);
                    if ($groupname!=null) {
                        $menu->groupname=$groupname;
                    } else {
                        $menu->groupname='';
                    }
                    $menu->name=$type->typestr;
                    $menu->description='';//$type-typestr;
                    $menu->addurl=$urlbase.$type->type;
                    $menu->helpurl='';
                    $menu->helptext='';
                    if ($sm->string_exists('modulename_help', $modname)) {
                        $menu->helptext = strip_tags(format_text(get_string('modulename_help', $modname), FORMAT_PLAIN, $formatopt));
                        if ($sm->string_exists('modulename_link', $modname)) {  // Link to further info in Moodle docs
                            $menu->helpurl = get_string('modulename_link', $modname);
                        }
                    }
                    $resources[$modname.':'.$type->type] = $menu;
                }
            }
        } else {
            $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            $menu = new stdClass();
            $menu->modname = $modname;
            $menu->type=$modname;
            $menu->archetype=$archetype;
            $menu->isactivity = ($archetype != MOD_ARCHETYPE_RESOURCE);
            $menu->groupname='';
            $menu->name=$modnamestr;
            $menu->description='';//$type-typestr;
            $menu->addurl=$urlbase.$modname;
            $menu->helpurl='';
            $menu->helptext='';
            if ($sm->string_exists('modulename_help', $modname)) {
                $menu->helptext = strip_tags(format_text(get_string('modulename_help', $modname), FORMAT_PLAIN, $formatopt));
                if ($sm->string_exists('modulename_link', $modname)) {  // Link to further info in Moodle docs
                    $menu->helpurl = get_string('modulename_link', $modname);
                }
            }

            $resources[$modname] = $menu;
        }
    }
    return $resources;
}

/**
 * Display the section that the user was last viewing
 *
 * @param stdClass $course
 * @param int $currentsection The current section number to be displayed
 */
function format_folderview_display_section($course, $currentsection) {
    global $DB;

    $section   = optional_param('section', -1, PARAM_INT);
    $topic     = optional_param('topic', -1, PARAM_INT);
    $sectionid = optional_param('sectionid', 0, PARAM_INT);
    if ($sectionid) {
        $section = $DB->get_field('course_sections', 'section', array('id' => $sectionid, 'course' => $course->id), MUST_EXIST);
    }
    if ($section != -1) {
        $section = format_folderview_course_set_display($course->id, $section);
    } else if ($topic != -1) {
        $section = format_folderview_course_set_display($course->id, $topic);
    } else {
        $section = format_folderview_course_get_display($course->id);
    }
    if ($section > 0 and $section != $currentsection) {
        $modinfo     = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);

        // If the user cannot view, go to all sections or they will see
        // infinite error loop
        if (!$sectioninfo->uservisible) {
            $section = 0;
        }
    }
    if ($section != $currentsection) {
        redirect(new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section)));
    }
}

/**
 * Restore course_get_display() method.
 *
 * Returns the course section to display or 0 meaning show all sections. Returns 0 for guests.
 * It also sets the $USER->display cache to array($courseid=>return value)
 *
 * @param int $courseid The course id
 * @return int Course section to display, 0 means all
 */
function format_folderview_course_get_display($courseid) {
    global $USER, $DB;

    $display = 0;

    if (!isset($USER->display[$courseid])) {
        if (isloggedin() and !isguestuser()) {
            if (!$display = $DB->get_field('format_folderview_display', 'display', array('userid' => $USER->id, 'course' => $courseid))) {
                $display = 0; // all sections option is not stored in DB, this makes the table much smaller
            }
        }
        //use display cache for one course only - we need to keep session small
        $USER->display = array($courseid => $display);
    }

    return $USER->display[$courseid];
}

/**
 * Restore course_set_display() method.
 *
 * Show one section only or all sections.
 *
 * @param int $courseid The course id
 * @param mixed $display show only this section, 0 or 'all' means show all sections
 * @return int Course section to display, 0 means all
 */
function format_folderview_course_set_display($courseid, $display) {
    global $USER, $DB;

    if ($display === 'all' or empty($display)) {
        $display = 0;
    }
    if (isloggedin() and !isguestuser()) {
        if ($display == 0) {
            //show all, do not store anything in database
            $DB->delete_records('format_folderview_display', array('userid' => $USER->id, 'course' => $courseid));

        } else {
            if ($DB->record_exists('format_folderview_display', array('userid' => $USER->id, 'course' => $courseid))) {
                $DB->set_field('format_folderview_display', 'display', $display, array('userid' => $USER->id, 'course' => $courseid));
            } else {
                $record          = new stdClass();
                $record->userid  = $USER->id;
                $record->course  = $courseid;
                $record->display = $display;
                $DB->insert_record('format_folderview_display', $record);
            }
        }
    }

    //use display cache for one course only - we need to keep session small
    $USER->display = array($courseid => $display);

    return $display;
}
