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
 * @author Mark Nielsen
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/folderview/lib.php');

/**
 * Renderer for outputting the folderview course format.
 *
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package format_folderview
 * @author David Mills
 * @author Mark Nielsen
 */
class format_folderview_renderer extends format_section_renderer_base {
    /**
     * Generate the starting container html for a list of sections
     *
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'folderview'));
    }

    /**
     * Generate the closing container html for a list of sections
     *
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     *
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    public function section_title($section, $course) {
        $title = get_section_name($course, $section);
        $url   = course_get_url($course, $section->section, array('sr' => $section->section));
        if ($url) {
            $title = html_writer::link($url, $title);
        }
        return $title;
    }

    protected function section_left_content($section, $course, $onsectionpage) {
        if ($onsectionpage or $section->section == 0) {
            return parent::section_left_content($section, $course, $onsectionpage);
        }
        $sectionname = get_section_name($course, $section);
        if ($section->uservisible) {
            $o = html_writer::link(
                course_get_url($course, $section->section, array('sr' => $section->section)),
                $this->output->pix_icon('spacer', get_string('sectionexpandcollapse', 'format_folderview', $sectionname), 'format_folderview', array('class' => 'folder_icon'))
            );
        } else {
            $o = $this->output->pix_icon('folder', get_string('sectionnotavailable', 'format_folderview', $sectionname), 'format_folderview');
        }
        if ($this->is_section_current($section, $course)) {
            $o .= get_accesshide(get_string('currentsection', 'format_'.$course->format));
        }
        return $o;
    }

    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        $title      = get_string('showonlytopic', 'format_folderview', get_section_name($course, $section));
        $img        = html_writer::empty_tag('img', array('src' => $this->output->pix_url('one', 'format_folderview'), 'class' => 'icon one', 'alt' => $title));
        $onesection = html_writer::link(course_get_url($course, $section->section, array('sr' => $section->section)), $img, array('title' => $title));

        if (!$PAGE->user_is_editing()) {
            if (!$onsectionpage) {
                return array($onesection);
            }
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();

        if (!$onsectionpage and has_capability('moodle/course:manageactivities', $coursecontext)) {
            $title      = get_string('sectionaddresource', 'format_folderview', get_section_name($course, $section));
            $img        = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/add'), 'class' => 'icon add', 'alt' => $title));
            $controls[] = html_writer::link('#tab_addResource', $img, array('title' => $title));
        }
        if (has_capability('moodle/course:update', $coursecontext)) {
            $url        = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $section->section));
            $img        = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'icon edit', 'alt' => get_string('edit')));
            $controls[] = html_writer::link($url, $img, array('title' => get_string('editsummary')));
        }
        if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) { // Show the "light globe" on/off.
                $url->param('marker', 0);
                $img        = html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'), 'class' => 'icon ', 'alt' => get_string('markedthistopic')));
                $controls[] = html_writer::link($url, $img, array('title' => get_string('markedthistopic'), 'class' => 'editing_highlight'));
            } else {
                $url->param('marker', $section->section);
                $img        = html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'), 'class' => 'icon', 'alt' => get_string('markthistopic')));
                $controls[] = html_writer::link($url, $img, array('title' => get_string('markthistopic'), 'class' => 'editing_highlight'));
            }
        }
        if (!$onsectionpage) {
            $controls[] = $onesection;
        }

        return array_reverse(
            array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage))
        );
    }

    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        $courseclone = clone($course);
        $courseclone->coursedisplay = COURSE_DISPLAY_SINGLEPAGE;

        echo html_writer::start_tag('div', array('class' => 'multi-section'));
        echo $this->all_sections_visibility_toggles();
        echo $this->output->heading(get_section_name($courseclone, $sections[0]), 2, 'mdl-align title headingblock header outline pagetitle', 'pagetitle');
        $this->action_menu($courseclone, $sections[0], $sections, $modnames);
        parent::print_multiple_section_page($courseclone, $sections, $mods, $modnames, $modnamesused);
        echo html_writer::end_tag('div');
    }


    /**
     * @param stdClass $course
     * @param section_info[] $sections
     * @param array $mods
     * @param array $modnames
     * @param array $modnamesused
     * @param int $displaysection
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        // Can we view the section in question?
        $context       = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);

        if (!isset($sections[$displaysection])) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sections[$displaysection]->visible && !$canviewhidden) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        $viewallicon = $this->output->action_icon(
            new moodle_url('/course/view.php', array('id' => $course->id, 'section' => 0)),
            new pix_icon('all', get_string('section0name', 'format_folderview'), 'format_folderview')
        );

        echo $this->output->box($viewallicon, 'topiclistlink', 'topiclinktop');

        // Title attributes
        $titleattr = 'mdl-align title headingblock header outline pagetitle';
        if (!$sections[$displaysection]->visible) {
            $titleattr .= ' dimmed_text';
        }
        echo $this->output->heading(get_section_name($course, $sections[$displaysection]), 2, $titleattr, 'pagetitle');
        $this->action_menu($course, $sections[$displaysection], $sections, $modnames);

        // Now the list of sections..
        echo $this->start_section_list();

        // The requested section page.
        $thissection = $sections[$displaysection];
        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        print_section($course, $thissection, $mods, $modnamesused, true, '100%', false, $displaysection);
        if ($PAGE->user_is_editing()) {
            print_section_add_menus($course, $displaysection, $modnames, false, false, $displaysection);
        }
        echo $this->section_footer();
        echo $this->end_section_list();

        $sectionmenu = array();
        foreach ($sections as $section) {
            if ($section->uservisible and $section->section != 0) {
                $sectionmenu[$section->section] = get_section_name($course, $section);
            }
        }
        if (!empty($sectionmenu)) {
            $viewall    = get_string('section0name', 'format_folderview');
            $viewallurl = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $viewall);

            array_unshift($sectionmenu, $viewall);
            $select         = new single_select(new moodle_url('/course/view.php', array('id' => $course->id)), 'section', $sectionmenu);
            $select->label  = get_string('jumpto');
            $select->class  = 'jumpmenu';
            $select->formid = 'sectionmenu';

            echo html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
            echo html_writer::tag('div', $viewallurl, array('class' => 'viewall'));
            echo $this->output->render($select);
            echo html_writer::end_tag('div');
        }

        // close single-section div.
        echo html_writer::end_tag('div');
    }

    /**
     * This renders the expand/collapse all sections
     * widgets.
     *
     * @return string
     */
    public function all_sections_visibility_toggles() {
        $strexpandall   = get_string('expandall', 'format_folderview');
        $strcollapseall = get_string('collapseall', 'format_folderview');
        $url            = new moodle_url('#');
        $expandicon     = $this->output->pix_icon('t/switch_plus', $strexpandall);
        $collapseicon   = $this->output->pix_icon('t/switch_minus', $strcollapseall);

        $output  = html_writer::link($url, $expandicon.get_accesshide($strexpandall), array('class' => 'expand-sections'));
        $output .= html_writer::link($url, $collapseicon.get_accesshide($strcollapseall), array('class' => 'collapse-sections'));

        return html_writer::tag('div', $output, array('id' => 'topiclinktop', 'class' => 'topiclistlink'));
    }

    /**
     * This renders the top tabs/menu for course administration
     *
     * @param stdClass $course
     * @param section_info $section
     * @param section_info[] $sections
     * @param array $modnames
     * @return void
     */
    public function action_menu($course, $section, $sections, $modnames) {
        global $USER;

        if (!$this->page->user_is_editing()) {
            return;
        }
        require_once(__DIR__.'/lib.php');

        $coursecontext = context_course::instance($course->id);

        $isroot = ($section->section == 0);
        $screenreader = !empty($USER->screenreader);
        $hascourseupdate     = has_capability('moodle/course:update', $coursecontext);
        $hasmanageactivities = has_capability('moodle/course:manageactivities', $coursecontext);

        if ($screenreader) {
            $screenreaderclass = ' screenreader';
        } else {
            $screenreaderclass = '';
        }

        echo "<div id=\"menuPanel\" class=\"nodialog$screenreaderclass\" style=\"border-collapse:collapse\">";
        echo "<div id=\"menuPanelTabs\" class=\"menuPanelTabs\">";

        // Action Menu - links for adding content and editing page
        if (!$screenreader) {
            if ($isroot && $hascourseupdate) {
                echo html_writer::tag('span', html_writer::link('#', get_string('addtopic', 'format_folderview')), array('class' => 'tab', 'id' => 'tab_addTopic'));
            }
            if ($hasmanageactivities) {
                echo html_writer::tag('span', html_writer::link('#', get_string('addresource', 'format_folderview')), array('class' => 'tab', 'id' => 'tab_addResource'));
            }
            if ($this->page->user_can_edit_blocks()) {
                echo html_writer::tag('span', html_writer::link('#', get_string('addblock', 'format_folderview')), array('class' => 'tab', 'id' => 'tab_addBlock'));
            }
        }
        if ($hascourseupdate) {
            echo html_writer::tag('span', html_writer::link(new moodle_url('/course/editsection.php?id='.$section->id), get_string('topicsettings', 'format_folderview')), array('class' => 'tab nodialog', 'id' => 'tab_editTopic'));
        }
        echo '</div>';

        // End of Action Menu

        echo '<div id="menuPanelDialog">';

        //output the Cancel button for all add dialogs
        if (!$screenreader) {
            $strclose = get_string('close', 'format_folderview');
            $icon     = $this->output->action_icon('#', new pix_icon('close', $strclose, 'format_folderview'), null, array('title' => $strclose));
            echo html_writer::tag('div', $icon, array('id' => 'menuPanelClose'));
        }
        echo $this->action_menu_add_topic_dialog($course);
        $this->action_menu_add_resources_dialog($course, $section, $sections, $modnames);
        echo $this->action_menu_add_block_dialog();

        echo '</div>'; //close dialog content area
        echo '</div>'; //close dialog container
    }

    /**
     * Renders dialog content for adding a new topic
     *
     * @param stdClass $course
     * @return string
     */
    public function action_menu_add_topic_dialog($course) {
        global $USER;

        $output = '';
        if (has_capability('moodle/course:update', context_course::instance($course->id))) {
            $url = new moodle_url('/course/format/folderview/addsection.php', array(
                'sesskey'  => sesskey(),
                'courseid' => $course->id,
            ));

            $output .= html_writer::start_tag('div', array('id' => 'addTopic', 'class' => 'dialog', 'tabindex' => '-1'));
            if (!empty($USER->screenreader)) {
                $output .= $this->output->heading(get_string('addtopic', 'format_folderview'), 3, '', 'tab_addTopic');
            }
            $output .= html_writer::start_tag('form', array('method' => 'post', 'action' => $url->out_omit_querystring()));
            $output .= html_writer::input_hidden_params($url);
            $output .= html_writer::tag('div', html_writer::label(get_string('sectiontitle', 'format_folderview'), 'newsection', true, array('class' => 'accesshide')));
            $output .= html_writer::empty_tag('input', array('id' => 'newsection', 'type' => 'text', 'size' => 50, 'name' => 'newsection', 'class' => 'focusonme'));
            $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'addtopic', 'value' => get_string('addtopic', 'format_folderview')));
            $output .= html_writer::end_tag('form');
            $output .= html_writer::end_tag('div');
        }
        return $output;
    }

    /**
     * Renders dialog content for adding a new activity
     *
     * @param stdClass $course
     * @param section_info $section
     * @param section_info[] $sections
     * @param array $modnames
     * @return void
     */
    public function action_menu_add_resources_dialog($course, $section, $sections, $modnames) {
        global $CFG, $USER;

        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
            return;
        }
        $screenreader = !empty($USER->screenreader);
        $straddresource = get_string('addresource', 'format_folderview');
        $strresources  = get_string('resources', 'format_folderview');
        $stractivities = get_string('activities');
        $straddtotopic = get_string('addtotopic', 'format_folderview');
        $modtypes = format_folderview_get_course_resource_types($course, $section->section, $modnames);


        echo '<div id="addResource" class="dialog" tabindex="-1">';
        if ($screenreader) {
            echo $this->output->heading($straddresource, 3, '', 'tab_addResource');
        }
        echo '<form method="GET" action="'.$CFG->wwwroot.'/course/format/folderview/addmod.php">';
        echo '<input type="hidden" name="id" value="'.$course->id.'" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        if (!$screenreader) {
            echo '<input id="addResourceHidden" type="hidden" name="add" value="" />';
        }

        $fieldsets = array('Activities' => array(), 'Assignments' => array(), 'Resources' => array());

        foreach ($modtypes as $modkey => $modtype) {

            $name = $modtype->name;
            $catname = $strresources;

            if ($modtype->isactivity) {
                $catname = $stractivities;
            }
            if ($modtype->groupname != '') {
                $catname = $modtype->groupname;
            }
            if (!isset($fieldsets[$catname])) {
                $fieldsets[$catname] = array();
            }
            if (!$screenreader) {
                $itemhtml = '<div id="add_'.$modkey.'" class="restype"><a href="#" id="add_mod_'.$modtype->type.'" title="'.s($modtype->helptext).'"><img src="'.$this->output->pix_url('icon', $modtype->modname).'" alt="'.s($name).'" border="0" hspace="2" />'.$name.'</a></div>';
            } else {
                $itemhtml = '<div id="add_'.$modkey.'" class=""><label><input type="radio" name="add" value="'.$modtype->type.'" /><img src="'.$this->output->pix_url('icon', $modtype->modname).'" alt="'.s($name).'" border="0" hspace="2" />'.$name.'</label></div>';
            }
            //Add item html its categories array
            array_push($fieldsets[$catname], $itemhtml);
        }

        $output      = "";
        $itemspercol = floor(get_string('itemspercolumn', 'format_folderview'));
        $numcols     = floor(get_string('numberofcolumns', 'format_folderview'));
        foreach ($fieldsets as $fsname => $fstext) {
            if (count($fstext) > 0) {
                $totalitems = count($fstext);
                $colitems   = $itemspercol;
                if ($colitems == 0) {
                    $colitems = ceil($totalitems / $numcols);
                }
                $output = $output.'<fieldset class="rescat"><legend>'.$fsname.'</legend><div class="column">';
                foreach ($fstext as $index => $item) {
                    if (($index != 0) and ($index % $colitems == 0)) {
                        $output = $output.'</div><div class="column">';
                    }
                    $output = $output.$item;
                }
                $output = $output.'</div><div class="clearfix"></div></fieldset>';
            }
        }
        echo $output;

        //Output topic selector (which defaults to current topic)
        if ($section->section != 0) {
            $currenttopic = " (".get_string('currenttopic', 'format_folderview').")";
        } else {
            $currenttopic = '';
        }
        echo '<div id="divAddToSection">';
        echo '<label>'.$straddtotopic.'<br /><select id="selAddToSection" name="section">';
        foreach ($sections as $id => $asection) {
            $label = get_section_name($course, $asection);
            if ($section->section == $id) {
                echo "<option value=\"$id\" selected=\"selected\">$label$currenttopic</option>";
            } else {
                echo "<option value=\"$id\">$label</option>";
            }
        }
        echo '</select></label> ';
        if ($screenreader) {
            echo '<input type="submit" name="do" value="'.$straddresource.'" />';
        }
        echo '</div>';

        echo '<div class="clearfix"></div></form></div>'; //close addResource
    }

    /**
     * Renders dialog content for adding a new block
     *
     * @return string
     */
    public function action_menu_add_block_dialog() {
        global $USER;
        if (!$this->page->user_is_editing() || !$this->page->user_can_edit_blocks() || !empty($USER->screenreader)) {
            return '';
        }
        $missingblocks = $this->page->blocks->get_addable_blocks();
        if (empty($missingblocks)) {
            return get_string('noblockstoaddhere');
        }

        $menu = array();
        foreach ($missingblocks as $block) {
            $blockobject = block_instance($block->name);
            if ($blockobject !== false && $blockobject->user_can_addto($this->page)) {
                $menu[$block->name] = $blockobject->get_title();
            }
        }
        collatorlib::asort($menu);

        foreach ($menu as $blockname => $blocktitle) {
            $menu[$blockname] = html_writer::link(new moodle_url($this->page->url, array('sesskey' => sesskey(), 'bui_addblock' => $blockname)), $blocktitle);
            $menu[$blockname] = html_writer::tag('div', $menu[$blockname]);
        }
        $output = '';

        $size = ceil(count($menu) / 3);

        foreach (array_chunk($menu, $size) as $chunk) {
            $output .= html_writer::tag('div', implode('', $chunk), array('class' => 'column'));
        }
        $output .= html_writer::tag('div', '&nbsp;', array('class' => 'clearfix'));
        return html_writer::tag('div', $output, array('id' => 'addBlock', 'class' => 'dialog', 'tabindex' => '-1'));
    }
}