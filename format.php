<?php
// Display the whole course as "folders" made of of modules
// Included from "view.php"
/**
 * @copyright &copy; 2010 David Mills
 * @author David Mills
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

$strtopiclisttitle = get_string('section0name', 'format_folderview');
$strsectionname = get_string('defaultsectionname', 'format_folderview');
$straddtopic = get_string('addtopic', 'format_folderview');
$straddresource = get_string('addresource', 'format_folderview');
$straddblock = get_string('addblock', 'format_folderview');
$streditlayout = get_string('editlayout', 'format_folderview');
$strtopicsettings = get_string('topicsettings', 'format_folderview');
$strdisplay = get_string('display', 'format_folderview');
$strexpandcollapse = get_string('expandcollapse', 'format_folderview');
$strviewall = get_string('viewall', 'format_folderview');
$strexpand = get_string('expand', 'format_folderview');
$strexpandall = get_string('expandall', 'format_folderview');
$strcollapseall = get_string('collapseall', 'format_folderview');
$straddtotopic = get_string('addtotopic', 'format_folderview');
$strdone = get_string('done', 'format_folderview');
$strclose = get_string('close', 'format_folderview');
$strresources = get_string('resources', 'format_folderview');
$stractivities = get_string('activities', 'format_folderview');
$streditmenu = get_string('editmenu', 'format_folderview');
$streditsummary = get_string('editsummary');
$stradd = get_string('add');
$stractivities = get_string('activities');
$strshowalltopics = get_string('showalltopics');
$strtopic = get_string('topic');
$strgroups = get_string('groups');
$strgroupmy = get_string('groupmy');
$strcancel =  get_string('cancel');
$strmove = get_string('move');
$editing = $PAGE->user_is_editing();

if ($editing) {
    $strtopichide = get_string('hidetopicfromothers');
    $strtopicshow = get_string('showtopicfromothers');
    $strmarkthistopic = get_string('markthistopic');
    $strmarkedthistopic = get_string('markedthistopic');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
}

//define constants for layout mode
define('COURSE_LAYOUT_COLLAPSED', 0);
define('COURSE_LAYOUT_EXPANDED', 1);
define('COURSE_LAYOUT_SINGLE', 2);
	
//define constants for default topic title format
define('TOPIC_DEFAULT_TOPIC', 0);
define('TOPIC_DEFAULT_WEEK', 1);
define('TOPIC_DEFAULT_FOLDER', 2);

$displaymode = optional_param('displaymode', COURSE_LAYOUT_COLLAPSED, PARAM_INT);
$titlemode = TOPIC_DEFAULT_FOLDER;

//Cache the course context for later use
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

//Cache highly reused course capability checks
$hasviewhiddensections = has_capability('moodle/course:viewhiddensections', $coursecontext);
$hascourseupdate = has_capability('moodle/course:update', $coursecontext);
$hasmanageactivities = has_capability('moodle/course:manageactivities', $coursecontext);

$screenreader = !empty($USER->screenreader);
$ajaxok = (!empty($USER->ajax) && !$screenreader);

//Process New Section Request
$newsection = optional_param('newsection', null, PARAM_TEXT);
$newsectionnum = optional_param('newsectionnum', 1, PARAM_INT);
if ($editing && $hascourseupdate && ($newsectionnum == $course->numsections+1) && confirm_sesskey()) {
	//fetch and/or create specified section
	$thissection = get_course_section($newsectionnum, $course->id);
	$DB->set_field('course_sections', 'name', $newsection, array('course'=>$course->id, 'section'=>$newsectionnum));
	$thissection->name = $newsection;
	$sections[$newsectionnum] = $thissection;
	$course->numsections = $newsectionnum;
	$DB->set_field('course', 'numsections', $newsectionnum, array('id'=>$course->id));
}

//Update marker if passed
if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    $DB->set_field("course", "marker", $marker, array("id"=>$course->id));
}

//Determine current section and persist setting if newly changed
$topicparam = "";

// TODO: Find a way to let user choose whether to output subtitle with the weekdays so we do not need separate formats for topics and weeks
$showsubtitle = 1;

//Determine 'focused' section and persist if changed
$topic = optional_param('topic', -1, PARAM_INT);
if (empty($sections[$topic])) { 
	$topic = 0;
}
if ($topic != -1) {
    $topic = course_set_display($course->id, $topic);
	$topicparam = "&topic=".$topic;
} else {
    if (isset($USER->display[$course->id])) {
        $topic = $USER->display[$course->id];
    } else {
        $topic = course_set_display($course->id, 0);
    }
}
$isroot = ($topic==0);
if (!$isroot) {
	$displaymode = COURSE_LAYOUT_SINGLE;
}

$section = 0;
//For single topic we output the current topic at the top
if ($displaymode == COURSE_LAYOUT_SINGLE) {
	$section = $topic;
}
$thissection = $sections[$section];


//Info needed for calculating week ranges for topics
$timenow = time();
$weekofseconds = 604800;
$weekdate = $course->startdate;  // this should be 0:00 Monday of that week
$weekdate += 7200 - $weekofseconds; // Add two hours to avoid possible DST problems then subtract a week so we can add at the start of each loop
$course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
$strftimedateshort = ' '.get_string('strftimedateshort');
$weekdate = $weekdate + ($weekofseconds);
$sectionweeks = array();
$sectionweeks['0'] = '';

//Create an array of the section names and force a default for the Topic 0
$strsectionnames = array();
$strsectionnames['0'] = $strtopiclisttitle;
if (!is_null($sections['0']->name) and $sections['0']->name !='') { 
	$strsectionnames['0'] = $sections['0']->name;
}

$x = 1;
while ($x <= $course->numsections) {
	$weekdate = $weekdate + ($weekofseconds);
	$nextweekdate = $weekdate + ($weekofseconds);
	$weekday = userdate($weekdate, $strftimedateshort);
	$endweekday = userdate($weekdate+518400, $strftimedateshort);
	$sectionweeks[$x] = $weekday.' - '.$endweekday;

	$thename = 'Topic '.$x;
	//Handle missing sections by creating them with a call to get_course_section which creates it with appropriate defaults
	if (empty($sections[$x])) {
		$sections[$x] = get_course_section($x, $course->id);
	}
	if (!is_null($sections[$x]->name) and $sections[$x]->name !='') { 
		$thename = $sections[$x]->name; 
	} else {
		$thename = get_section_name($course, $sections[$x]);
	}
	$strsectionnames[$x] = $thename;
	$x++;
}

//Create Section Menu array and Add the Topic Outline page to the list of sections
$sectionmenu = array();
// Add item to section menu list
$sectionmenu['0'] = $strtopiclisttitle;

//Output CSS required by format
echo '<style type="text/css"> ';

if ($ajaxok) {
	//Drag-n-drop CSS
	echo '.editlayout .draghandle { display:inline; } ';
	echo '.nodisplay { display:none; } ';
	echo '.draghandle { display:none;float:left; } ';
	echo '.pagetopic .dragsection { display:none !important; } ';
	echo '.editlayout .course-content ul.topics li.section .left>div  { float:right; }';
	echo '.editlayout .course-content ul.topics li.section .left {width:70px;max-width:70px;} ';
	echo '.editlayout .course-content ul.topics li.section .content {margin-left:70px;} ';
	echo '.editlayout .right.side { display:none; } ';
	echo '.right.side>div { margin-bottom:3px; } ';
	//echo '.dragactivity { margin-right:3px;float:left;display:none; }';
	echo '.dragsection img { width:24px; height:24px; }';
	echo '.dragactivity img { width:16px; height:16px; }';
	//a max-height and overflow hidden are specified to make dnd easier with long label items
	echo '.editlayout li.activity { border:1px dotted #cccccc; }';
	echo '.editlayout .topicmenu { display:none; }';
}

//Screen reader CSS
if (!$screenreader) {
	echo '.dialog { display:none; } ';
	echo '#menuPanelClose { display:block; text-align:right; } ';
	echo '#menuPanelDialog { border:1px solid; padding:10px; } ';
	//The negative border is to allow the selected tab border to cover up the panel border so they appear connected
	echo '.menuPanelTabs { margin-bottom:-1px; } ';
	echo '.menuPanelTabs .tab { border-bottom:1px solid; padding: 3px 6px; } ';
	echo '.nodialog .menuPanelTabs .tab { border:0px none; } ';
	echo '.nodialog #menuPanelClose { display:none; } ';
	echo '.nodialog #menuPanelDialog { border:0px none;padding:0px;margin:0px;-moz-border-radius:none; -webkit-border-radius:none; border-radius:none;-moz-box-shadow:none;-webkit-box-shadow:none;box-shadow:none; } ';
	echo '.dlg_addResource #addResource { display:block; min-height:1em; } ';
	echo '#addResource .column { float:left; padding-right:1em; } ';
	echo '#addResource .column .restype { padding:1px 0px; } ';
	echo '#divAddToSection { clear:both; margin-top:1em; } ';
	echo '.dlg_addActivity #addActivity { display:block; } ';
	echo '.dlg_addBlock #addBlock { display:block; } ';
	echo '.dlg_addTopic #addTopic { display:block; } ';
	echo '.dlg_editLayout #editLayout { display:block; } ';	
	//Make the bottom border white so it appears connected to the dialog panel
	echo '.dlg_addActivity #tab_addActivity, .dlg_addBlock #tab_addBlock, .dlg_addTopic #tab_addTopic, .dlg_addResource #tab_addResource, .dlg_editLayout #tab_editLayout { border:1px solid; border-bottom-color:white;  } ';
	echo '.menuPanelTabs .tab { display:inline-block; } ';
} else {
	echo '.dialog { border:1px solid #999999; padding:0px 1em 1em; margin:1em 0px; }';
}


echo '</style>';

/******************** Expand All/Collapse All/Topic List *******************/
if ($displaymode == COURSE_LAYOUT_SINGLE && $topic != 0) {
	//Show a link to the topics list if not on the topics list page
	echo '<div id="topiclinktop" class="topiclistlink">';
	if ($section!=0) {
		if ($editing && $hascourseupdate) {
			if ($thissection->visible) {        // Show the hide/show eye
				echo '&nbsp;&nbsp;<a href="view.php?id='.$course->id.'&amp;topic='.$section.'&amp;hide='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopichide.'">'.
					'<img src="'.$OUTPUT->pix_url('t/hide').'" class="icon" alt="'.$strtopichide.'" /></a>';
			} else {
				echo '&nbsp;&nbsp;<a href="view.php?id='.$course->id.'&amp;topic='.$section.'&amp;show='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopicshow.'">'.
					'<img src="'.$OUTPUT->pix_url('t/show').'" class="icon" alt="'.$strtopicshow.'" /></a>';
			}
		}
		echo '&nbsp;&nbsp;<a href="view.php?id='.$course->id.'&topic=0&sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopiclisttitle.'"><img src="'.$OUTPUT->pix_url('all','format_folderview').'" class="icon" alt="'.$strtopiclisttitle.'" /></a>';
	}
	echo '</div>';
} else {
	echo '<div id="topiclinktop" class="topiclistlink"><a href="javascript:void(M.format_folderview.expandSection())" title="'.$strexpandall.'"><img src="'.$OUTPUT->pix_url('t/switch_plus').'" alt="'.$strexpandall.'" /></a> <a href="javascript:void(M.format_folderview.collapseSection())" title="'.$strcollapseall.'"><img src="'.$OUTPUT->pix_url('t/switch_minus').'" alt="'.$strcollapseall.'" /></a></div>';
}

//Make sure user can view the current topic
$showsection = ($hasviewhiddensections or $thissection->visible or !$course->hiddensections);

/******************** Page Title *******************/
//Write out the page title (and Completion info if topic 0)
if ($showsection) {
	if ($thissection->section == 0) {
    	// Print the Your progress icon if the track completion is enabled
    	$completioninfo = new completion_info($course);
    	echo $completioninfo->display_help_icon();
	}

	// Output the item header as the page title
	echo $OUTPUT->heading($strsectionnames[$thissection->section], 2, 'headingblock header outline pagetitle', 'pagetitle');
	if ($showsubtitle) {
		echo '<div class="pagesubtitle">'.$sectionweeks[$thissection->section].'</div>';
	}
}

/*********** Action Menu/Editor Dialog **************/
if ($showsection && $editing) {

	$modtypes = get_course_resource_types($course, $section, $modnames);

    echo '<div id="menuPanel" class="nodialog" cellspacing=0 style="border-collapse:collapse">';

	// TODO: Make sure we are checking for appropriate capabilities for each action
	//Action Menu - links for adding content and editing page
	if (!$screenreader) {
	    echo '<div id="menuPanelTabs" class="menuPanelTabs">';
    	if ($isroot && $hascourseupdate) {
			echo '<span class="tab" id="tab_addTopic"><a href="javascript:void(M.format_folderview.showMenuPanel(\'addTopic\', \'newsection\'));">'.$straddtopic.'</a></span>';
	    }
		if ($hasmanageactivities) {
	    	echo '<span class="tab" id="tab_addResource"><a href="javascript:void(M.format_folderview.showMenuPanel(\'addResource\'));" onclick="document.getElementById(\'selAddToSection\').selectedIndex='.$section.';">'.$straddresource.'</a></span>';
		}
		if ($hascourseupdate) {
	    	echo '<span class="tab" id="tab_addBlock"><a href="javascript:void(M.format_folderview.showMenuPanel(\'addBlock\'));">'.$straddblock.'</a></span>';
		}
		if ($hascourseupdate && $ajaxok) {
	    	echo '<span class="tab" id="tab_editLayout"><a href="javascript:void(M.format_folderview.showMenuPanel(\'editLayout\'));">'.$streditlayout.'</a></span>';
		}
		if ($hascourseupdate) {
	    	echo '<span class="tab" id="tab_editTopic"><a href="editsection.php?id='.$thissection->id.'">'.$strtopicsettings.'</a></span>';
		}
	    echo '</div>';
	} else {
		if ($hascourseupdate) {
		    echo '<div class="menuPanelTabs">';
    		echo '<span class="tab" id="tab_editTopic"><a href="editsection.php?id='.$thissection->id.'">'.$strtopicsettings.'</a></span>';
	    	echo '</div>';
		}
	}
	// End of Action Menu

    echo '<div id="menuPanelDialog">';

	//output the Cancel button for all add dialogs
	if (!$screenreader) {
    	echo '<div id="menuPanelClose" style="float:right"><a href="javascript:void(M.format_folderview.hideMenuPanel());" title="'.$strclose.'"><img alt="'.$strclose.'" src="'.$OUTPUT->pix_url('close','format_folderview').'" border="0" /></a></div>';
	}

	//Add Topic
	if ($hascourseupdate) {
		$newsectionnum = $course->numsections + 1;
	    echo '<div id="addTopic" class="dialog" tabindex="-1">';
		if ($screenreader) { 
	        echo $OUTPUT->heading($straddtopic, 3, null, 'tab_addTopic');
		}
		echo '<form method="GET" action="view.php">';
	    echo '<input type="hidden" name="id" value="'.$course->id.'" />';
	    echo '<input type="hidden" name="topic" value="'.$topic.'" />';
	    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
	    echo '<input type="hidden" name="newsectionnum" value="'.$newsectionnum.'" />';
		if ($screenreader) {
			echo '<div><label for="newsection">'.get_string('sectiontitle', 'format_folderview').'</label></div>';
		}
	    echo '<input id="newsection" type="text" size="50" name="newsection" value="" />';
		echo '<input type="submit" name="addtopic" value="'.$straddtopic.'" />';
		echo '</form>';
	    echo '</div>'; //close addTopic
	}

	//Add Resources section
	if ($hasmanageactivities) {
    	echo '<div id="addResource" class="dialog" tabindex="-1">';
		if ($screenreader) { 
	        echo $OUTPUT->heading($straddresource, 3, null, 'tab_addResource');
		}
		echo '<form method="GET" action="'.$CFG->wwwroot.'/course/mod.php">';
		echo '<input type="hidden" name="id" value="'.$course->id.'" />';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

		$fieldsets = array('Activities'=>array(), 'Assignments'=>array(), 'Resources'=>array());
		$fieldsetcounts = array();

		foreach ($modtypes as $modkey => $modtype) {

			$name = $modtype->name;

			$catname = $strresources;

			if ($modtype->isactivity) {
				$catname = $stractivities;
			}

			if ($modtype->groupname!='') {
				$catname = $modtype->groupname;
			}

			if (!isset($fieldsets[$catname])) {
				$fieldsets[$catname] = array();
			}
			if (!$screenreader) {
				$itemhtml = '<div id="add_'.$modkey.'" class="restype"><a href="javascript:void(M.format_folderview.addResource(\''.$modtype->type.'\'))" title="'.$modtype->helptext.'"><img src="'.$OUTPUT->pix_url('icon',$modtype->modname).'" alt="'.$name.'" border="0" hspace="2" />'.$name.'</a></div>';
			} else {
				$itemhtml = '<div id="add_'.$modkey.'" class=""><label><input type="radio" name="add" value="'.$modtype->type.'" /><img src="'.$OUTPUT->pix_url('icon',$modtype->modname).'" alt="'.$name.'" border="0" hspace="2" />'.$name.'</label></div>';
			}

			//Add item html its categories array
			array_push($fieldsets[$catname], $itemhtml);
		}

		$output = "";
		$itemspercol = floor(get_string('itemspercolumn', 'format_folderview'));
		$numcols = floor(get_string('numberofcolumns', 'format_folderview'));
		foreach ($fieldsets as $fsname => $fstext) {
			if (count($fstext)>0) {
				$totalitems = count($fstext);
				$colitems = $itemspercol;
				if ($colitems==0) {
					$colitems = ceil($totalitems/$numcols);
				}
				$output = $output.'<fieldset class="rescat"><legend>'.$fsname.'</legend><div class="column">';
				foreach ($fstext as $index => $item) {
					if (($index!=0) and ($index%$colitems == 0)) {
						$output = $output.'</div><div class="column">';
					}
					$output = $output.$item;
				}
				$output = $output.'</div><div class="fixfloat"></div></fieldset>';
			}
		}
		echo $output;

		//Output topic selector (which defaults to current topic)
		$currenttopic = '';
		if ($displaymode == COURSE_LAYOUT_SINGLE) {
			$currenttopic = " (".get_string('currenttopic', 'format_folderview').")";
		}
		echo '<div id="divAddToSection">';
		echo '<label>'.$straddtotopic.'<br /><select id="selAddToSection" name="section">';
		foreach ($strsectionnames as $id => $label) {
			if ($thissection->section == $id) {
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

	    echo '<div class="fixfloat"></div></form></div>'; //close addResource
	}

	//Add Block section
	if ($hascourseupdate && !$screenreader) {
	    echo '<div id="addBlock" class="dialog" tabindex="-1">';
    	echo '</div>'; //close addBlock
	}

	//Edit Layout section
	if ($hascourseupdate && $ajaxok) {
	    echo '<div id="editLayout" class="dialog" tabindex="-1">';
		echo '<div>'.get_string('editlayoutdirections', 'format_folderview').'</div>';
    	echo '</div>'; //close editLayout
	}

    echo '</div>'; //close dialog content area
    echo '</div>'; //close dialog container

}
/*********** END of Action Menu/Editor Dialog **************/

if ($screenreader) {
	echo $OUTPUT->heading(get_string('pagecontent', 'format_folderview'), 3, null, 'pagecontent');
}

// We always output an unordered list even if we are not listing all topics for interface items such as the clipboard
echo "<ul class='topics'>\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id)) {
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
    echo '<li class="clipboard">';
    echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.sesskey().'">'.$strcancel.'</a>)';
    echo "</li>\n";
}


/******************** Main Topic Content *******************/
if ($showsection) {
    echo '<li id="section-'.$thissection->section.'" class="section main clearfix pagetopic" >';
	if (!$hasviewhiddensections and !$thissection->visible) {   // Hidden for students
		echo '<div class="content"><div class="summary">'.get_string('notavailable').'</div></div>';
	} else {
	    echo '<div class="left side"></div>';
		//Hide the right side items for the page topic section via nodisplay class
		echo '<div class="right side nodisplay"></div>';
	    echo '<div class="content">';
	    	echo '<div class="summary">';
            if ($thissection->summary) {
                $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
                $summaryformatoptions = new stdClass();
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;
                echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);
            } else {
                echo '<div class="fixfloat">&nbsp;</div>';
            }
	    	echo '</div>';
		    print_section($course, $thissection, $mods, $modnamesused);
	    echo '</div>';
	}
    echo "</li>\n";
}

/// Now output all of the normal modules by topic if NOT in single item mode
/// Everything below uses "section" terminology - each "section" is a topic.

$timenow = time();
$section = 1;
$strcollapsedstyle = "";
if ($displaymode == COURSE_LAYOUT_COLLAPSED) {
	$strcollapsedstyle = " collapsed";
}
//Unset Topic 0
unset($sections['0']);

$listtopics = ($isroot or ($displaymode != COURSE_LAYOUT_SINGLE));
while ($section <= $course->numsections) {

    if (!empty($sections[$section])) {
        $thissection = $sections[$section];
    } else {
		//Maybe change this, do we Really need at least one topic besides topic 0?
        $thissection = new stdClass;
        $thissection->course  = $course->id;   // Create a new section structure
        $thissection->section = $section;
        $thissection->name    = null;
        $thissection->summary  = '';
        $thissection->summaryformat = FORMAT_HTML;
        $thissection->visible  = 1;
        //$thissection->id = $DB->insert_record('course_sections', $thissection);
    }

	$showsection = (!empty($sections[$section]) && ($hasviewhiddensections or $thissection->visible or !$course->hiddensections));

    $currenttopic = ($thissection->section == $topic);

	if ($showsection) {
        $sectionmenu[$section] = $strsectionnames[$section];
	}
	
	if ($listtopics && $showsection) {
	
		$strshowonlytopic = get_string("showonlytopic", "", $section);
		$linkurl = "view.php?id=$course->id&amp;topic=$section&amp;sesskey=".sesskey();
		$linktitle = $strshowonlytopic;
		$folderurl = "javascript:void(M.format_folderview.toggleSection('$section'))";
		$foldertitle = "$strsectionname $section: $strexpandcollapse";
		if ($displaymode == COURSE_LAYOUT_SINGLE) {
			$folderurl = $linkurl;
			$foldertitle = $linktitle;
		}

        $currenttopic = ($course->marker == $section);

        $currenttext = '';
        if (!$thissection->visible) {
            $sectionstyle = ' hidden';
        } else if ($currenttopic) {
            $sectionstyle = ' current';
            $currenttext = get_accesshide(get_string('currenttopic','access'));
        } else {
            $sectionstyle = '';
        }

		// TODO: Add logic for expand collapse based on toggle cookie
		if ($displaymode != COURSE_LAYOUT_EXPANDED) {
			$strcollapsedstyle = ' collapsed';
		} else {
			$strcollapsedstyle = '';
		}
	
        echo '<li id="section-'.$section.'" class="section main clearfix'.$sectionstyle.$strcollapsedstyle.'">'; 

		echo '<div class="left side">';
		echo '<div class="topic_bullet">';
		echo '<a id="folder_link_'.$section.'" href="'.$folderurl.'" title="'.$foldertitle.'" class="folder_link"><img class="folder_icon" src="'.$OUTPUT->pix_url('spacer','format_folderview').'" border="0" alt="'.$section.'." /></a>';
		echo '</div>'; //topic_bullet
		echo '</div>'; //left side

        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';
		echo '<div><a href="view.php?id='.$course->id.'&amp;topic='.$section.'&amp;sesskey='.sesskey().'" title="'.$strshowonlytopic.'">'.
			'<img src="'.$OUTPUT->pix_url('i/one').'" class="icon" alt="'.$strshowonlytopic.'" /></a></div>';
        if ($editing && $hascourseupdate) {
			if ($course->marker == $section) { // Show the "light globe" on/off
				echo '<div><a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkedthistopic.'">'.'<img src="'.$OUTPUT->pix_url('i/marked') . '" alt="'.$strmarkedthistopic.'" /></a></div>';
            } else {
				echo '<div><a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkthistopic.'">'.'<img src="'.$OUTPUT->pix_url('i/marker') . '" alt="'.$strmarkthistopic.'" /></a></div>';
            }

            if ($thissection->visible) {        // Show the hide/show eye
                echo '<div><a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopichide.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/hide').'" class="icon" alt="'.$strtopichide.'" /></a></div>';
            } else {
                echo '<div><a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strtopicshow.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/show').'" class="icon" alt="'.$strtopicshow.'" /></a></div>';
            }

            if (!$ajaxok && $section > 1) {                       // Add a arrow to move section up
                echo '<div><a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.sesskey().'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/up') . '" class="icon up" alt="'.$strmoveup.'" /></a></div>';
            }

            if (!$ajaxok && $section < $course->numsections) {    // Add a arrow to move section down
                echo '<div><a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.sesskey().'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/down').'" class="icon down" alt="'.$strmovedown.'" /></a></div>';
            }

        }
        echo '</div>';
		/* Have to add new icons in a different right-aligned div because DND wireup is removing them */
		if ($editing) {
	        echo '<div class="right side">';
			if ($editing && $hascourseupdate) {
				echo "<div><a href=\"editsection.php?id=$thissection->id\" title=\"$strsectionname $section: $strtopicsettings\"><img alt=\"$strsectionname $section: $strtopicsettings\" class=\"icon edit\" hspace=\"2\" src=\"".$OUTPUT->pix_url('t/edit')."\" /></a></div>";
			}

			echo "<div><a href=\"#tab_addResource\" onclick=\"M.format_folderview.showMenuPanel('addResource');document.getElementById('selAddToSection').selectedIndex=$section;\" title=\"$strsectionname $section: $straddresource\"><img alt=\"$strsectionname $section: $straddresource\" class=\"icon add\" hspace=\"2\" src=\"".$OUTPUT->pix_url('t/add')."\" /></a></div>";
	        echo '</div>';
		}

        echo '<div class="content">';
		echo $OUTPUT->heading('<a href="'.$linkurl.'" title="'.$linktitle.'">'.$strsectionnames[$thissection->section].'</a>', 3, 'sectionname');
		if ($showsubtitle) {
			echo '<div class="sectionsubtitle">'.$sectionweeks[$thissection->section].'</div>';
		}
		//Always output content for expand/collapsed, CSS will be used to hide/show contents
		if ($displaymode != COURSE_LAYOUT_SINGLE) {
        	if (!$hasviewhiddensections and !$thissection->visible) {   // Hidden for students
            	echo '<div class="summary">'.get_string('notavailable').'</div>';
			} else {
				echo '<div class="summary">';
				if ($thissection->summary) {
					$summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
					$summaryformatoptions = new stdClass();
					$summaryformatoptions->noclean = true;
					$summaryformatoptions->overflowdiv = true;
					echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);
				} else {
					echo '<div class="fixfloat">&nbsp;</div>';
				}
				echo '</div>'; //summary
				print_section($course, $thissection, $mods, $modnamesused);
			}
        }
		echo '</div>'; //content
		echo "</li>\n";
	}

	//Remove section from array
    unset($sections[$section]);
	//Increment to process next section
    $section++;
}

if ($editing and $hascourseupdate) {
    // print stealth sections if present
    $modinfo = get_fast_modinfo($course);
    foreach ($sections as $section=>$thissection) {
        if (empty($modinfo->sections[$section])) {
            continue;
        }
        echo '<li id="section-'.$section.'" class="section main clearfix orphaned hidden">'; 
        echo '<div class="left side">';
        echo '</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';
        echo '</div>';
        echo '<div class="content">';
        echo $OUTPUT->heading(get_string('orphanedactivities'), 3, 'sectionname');
        print_section($course, $thissection, $mods, $modnamesused);
        echo '</div>';
        echo "</li>\n";
    }
}


echo "</ul>\n";


echo '<div>&nbsp;</div>';
if ($displaymode == COURSE_LAYOUT_SINGLE) {
	echo '<div id="topiclinkbottom" class="topiclistlink"><a href="view.php?id='.$course->id.'&topic=0&sesskey='.sesskey().'">'.$strtopiclisttitle.'</a></div>';
}
if (!empty($sectionmenu)) {
	$select = new single_select(new moodle_url('/course/view.php?id='.$course->id.'&amp;sesskey='.sesskey(), array('id'=>$course->id)), 'topic', $sectionmenu);
	$select->label = get_string('jumpto');
	$select->class = 'jumpmenu';
	$select->formid = 'sectionmenu';
	echo $OUTPUT->render($select);
}

//Include format-specific javascript and initialize it
$arguments = array(
                'wwwroot' => $CFG->wwwroot,
                'courseid' => $course->id,
                'siteshortname' => $SITE->shortname,
                'numsections' => $course->numsections,
                'marknum' => $course->marker,
                'ajaxok' => $ajaxok,
                'screenreader' => $screenreader,
                'expandtext' => $streditmenu
            );
$module = array(
           'name' => 'format_folderview',
           'fullpath' => '/course/format/folderview/lib.js',
           'requires' => array(
               'yui2-event',
               'yui2-cookie'
           ),
       );

$PAGE->requires->js_init_call(
   'M.format_folderview.init',
   $arguments,
   true,
   $module
);


