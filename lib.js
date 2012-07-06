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

M.format_folderview = M.format_folderview || {};

var format_folderview_vars = {
	'wwwroot':'',
	'courseid':'',
	'numsections':0,
	'cookiename':'mdl_cf_folderview',
	'subname':'',
	'cookie':'',
	'toggles':[],
	'base32': {
		'0':'00000','1':'00001','2':'00010','3':'00011','4':'00100','5':'00101','6':'00110','7':'00111','8':'01000','9':'01001','A':'01010','B':'01011','C':'01100','D':'01101','E':'01110','F':'01111',
		'00000':'0','00001':'1','00010':'2','00011':'3','00100':'4','00101':'5','00110':'6','00111':'7','01000':'8','01001':'9','01010':'A','01011':'B','01100':'C','01101':'D','01110':'E','01111':'F',
		'G':'10000','H':'10001','I':'10010','J':'10011','K':'10100','L':'10101','M':'10110','N':'10111','O':'11000','P':'11001','Q':'11010','R':'11011','S':'11100','T':'11101','U':'11110','V':'11111',
		'10000':'G','10001':'H','10010':'I','10011':'J','10100':'K','10101':'L','10110':'M','10111':'N','11000':'O','11001':'P','11010':'Q','11011':'R','11100':'S','11101':'T','11110':'U','11111':'V'
	},
	'BASE32CHARS':'01234567890ABCDEFGHIJKLMNOPQRSTUV'
};


M.format_folderview.hideContextMenu = function () {
	clearTimeout(window.cmtimer);
	if (window.cmenu) {
		window.cmenu.style.display = 'none';
		window.cmenu = null;
	}
};

M.format_folderview.toggleMenu = function (el) {
	var displaystate = el.style.display;
	M.format_folderview.hideContextMenu();
	if (displaystate!='block') {
		el.style.left = (el.previousSibling.offsetLeft)+'px';
		el.style.top = (el.previousSibling.offsetTop+el.previousSibling.offsetHeight)+'px';
		el.style.display = 'block';
		window.cmenu = el;
		window.cmtimer = setTimeout(M.format_folderview.hideContextMenu, 5000);
	} else {
		el.style.display = 'none';
	}
};

M.format_folderview.showMenuPanel = function (id, focusId) {
	//first clean up any previous panel
	M.format_folderview.hideMenuPanel();
	//Now show the specified dialog
	document.getElementById('menuPanel').className = 'dlg_' + id;
	YAHOO.util.Dom.addClass(document.body, id.toLowerCase());
	//If Edit Layout we need to also add the class to the body so we can target elements elsewhere
//	if (id == 'editLayout') {
//		document.body.className += ' editlayout';
//	}
	//If a focus element was provided attempt to focus it
	if (focusId != null) {
		try { document.getElementById(focusId).focus(); } catch(e) { }
	}
};

M.format_folderview.hideMenuPanel = function (focusId) {
	var menuPanel = document.getElementById('menuPanel');
	YAHOO.util.Dom.removeClass(document.body, menuPanel.className.replace('dlg_', '').toLowerCase());
	//Change class on dialog to hide it
	menuPanel.className = 'nodialog';
	//Remove class from body to quit layout mode
	//document.body.className = document.body.className.replace(' editlayout', '');
	//If a focus element was provided attempt to focus it
	if (focusId != null) {
		try { document.getElementById(focusId).focus(); } catch(e) { }
	}
};

M.format_folderview.addResource = function (resType) {
	document.location.href='mod.php?id='+escape(format_folderview_vars.courseid)+'&section='+document.getElementById('selAddToSection').selectedIndex+'&add='+resType;
};

M.format_folderview.toggleSection = function (id, bFocus) {
	var el = document.getElementById('section-'+id);
	if (el) {
		var c = el.className;
		if (c.indexOf(' collapsed')==-1) {
			M.format_folderview.collapseSection(id, bFocus);
		} else {
			M.format_folderview.expandSection(id, bFocus);
		}
	}
};

M.format_folderview.expandSection = function (id, bFocus, bLoading) {
	var lis = [0];
	if (id > 0) { lis[1] = document.getElementById('section-'+id); }
	else {
        for( x = 1; x < format_folderview_vars.numsections+1; x++ ){
            lis[x] = document.getElementById('section-'+x);
        }
    }
	for (var x=1; x<lis.length; x++) {
		var el = lis[x];
		if (el && el.className && el.id) {
			var elid = lis[x].id.replace('section-', '');
			var c = el.className;
			if (c.indexOf(' collapsed')!=-1) {
				c = c.replace(' collapsed', '');
				el.className = c;
			}
			format_folderview_vars.toggles[elid] = 1;
			if (x==0 && bFocus && bFocus===true) { try { document.location.hash = "#"+el.id; } catch(e) { } }
		}
	}
	if (!bLoading) { M.format_folderview.saveToggles(); }
};

M.format_folderview.collapseSection = function (id, bFocus, bLoading) {
	var lis = [0];
	if (id > 0) { lis[1] = document.getElementById('section-'+id); }
	else {
        for( x = 1; x < format_folderview_vars.numsections+1; x++ ){
            lis[x] = document.getElementById('section-'+x);
        }
    }
	for (var x=1; x<lis.length; x++) {
		var el = lis[x];
		if (el && el.className && el.id) {
			var elid = lis[x].id.replace('section-', '');
			var c = el.className;
			if (c.indexOf(' collapsed')==-1) {
				c += ' collapsed';
				el.className = c;
			}
			format_folderview_vars.toggles[elid] = 0;
			if (x==0 && bFocus && bFocus===true) { try { document.location.hash = "#"+el.id; } catch(e) { } }
		}
	}
	if (!bLoading) { M.format_folderview.saveToggles(); }
};

M.format_folderview.saveToggles = function () {
	var ck = format_folderview_vars.toggles.join('')+'000000000000000000000000000000000000000000000000000000000000';
	var hex = [];
	for (var x=0; x<=format_folderview_vars.numsections+1; x+=5) {
		var binStr = ck.substr(x,5);
		var hexStr = (format_folderview_vars.base32[binStr]!='undefined')?format_folderview_vars.base32[binStr]:'0';
		hex.push(hexStr);
	}
	format_folderview_vars.cookie = hex.join('');
	M.format_folderview.saveCookie();
};

M.format_folderview.loadToggles = function () {
	format_folderview_vars.toggles = [];
	var toggleStr = format_folderview_vars.cookie;
	for (var x=0; x<toggleStr.length; x++) {
		var hexStr = toggleStr.charAt(x);
		if (format_folderview_vars.BASE32CHARS.indexOf(hexStr)==-1) { hexStr='0';alert('invalid char'+hexStr); }
		var binStr = format_folderview_vars.base32[hexStr];
		for (var y=0; y<binStr.length; y++) {
			format_folderview_vars.toggles.push(parseInt(binStr.charAt(y)));
		}
	}

	//add missing elements
	while (format_folderview_vars.length < format_folderview_vars.numsections) {
		format_folderview_vars.toggles.push(0);
	}
	//truncate if extra elements
	format_folderview_vars.toggles.length = format_folderview_vars.numsections+1;
};

M.format_folderview.saveCookie = function () {
	YAHOO.util.Cookie.setSub(format_folderview_vars.cookiename,format_folderview_vars.subname, format_folderview_vars.cookie);
};

M.format_folderview.refreshToggleState = function (bLoading) {
	for (var x=1; x<format_folderview_vars.toggles.length; x++) {
		if (format_folderview_vars.toggles[x]==1) {
			M.format_folderview.expandSection(x, false, bLoading);
		} else {
			M.format_folderview.collapseSection(x, false, bLoading);
		}
	}
};

// Initialise with the information supplied from the course format 'format.php' so we can operate.
// Args - wwwroot is the URL of the Moodle site, moodleid is the site short name (courseid 0) and courseid is the id of the current course to allow for settings for each course.
M.format_folderview.init = function (Y, wwwroot, courseid, siteshortname, numsections, marknum, ajaxok, screenreader, expandtext)
{
	var d = YAHOO.util.Dom;

	// Init.
	format_folderview_vars.wwwroot = wwwroot; //main.portal.strings['wwwroot'];
	format_folderview_vars.courseid = courseid; //main.portal.id;
	format_folderview_vars.numsections = parseInt(numsections); //main.portal.numsections;
	format_folderview_vars.subname = siteshortname.replace(/[^A-Za-z0-9]/g)+format_folderview_vars.courseid.toString();
	format_folderview_vars.movetext = 'Move';
	if (d.hasClass(document.body, 'drag')) {
		try { format_folderview_vars.movetext = main.portal.strings['move']; } catch(e) {}
	}

	//Fix flipped summaries and section lists that happens for some reason when section list s empty
	var sums = d.getElementsByClassName('section', 'ul');
	for (var x=0; x<sums.length; x++) {
		try {
			var nextSibling = d.getNextSibling(sums[x]);
			if (nextSibling && d.hasClass(nextSibling, 'summary')) {
				d.insertBefore(nextSibling, sums[x]);
			}
		} catch(e) { }
	}

	//Fill Add Block tab using block's select element
	if (!screenreader) {
		var blocks = '';
		var form = document.getElementById('add_block');
		if (form && form.elements['bui_addblock'] && form.elements['bui_addblock'].length>0) {
			var colitems = parseInt((form.elements['bui_addblock'].length+1)/3);
			var addblockurl = format_folderview_vars.wwwroot + '/course/view.php?id='+form.elements['id'].value+'&sesskey='+form.elements['sesskey'].value+'&bui_addblock=';
			var icon = '<img alt="" src="'+format_folderview_vars.wwwroot+'/pix/t/addfile.png" border="0" hspace="2" align="textbottom" />';
			blocks = '<div class="column">';
			for (var x=1; x<form.elements['bui_addblock'].length; x++) {
				var opt = form.elements['bui_addblock'].options[x];
				if ((x-1)%colitems==0) { blocks += '</div><div class="column">'; }
				blocks += '<div><a href="'+addblockurl+opt.value+'">'+opt.text+'</a></div>\n';
			}
			blocks += '</div><div class="fixfloat">&nbsp;</div>';
			if (document.getElementById('addBlock')) {
				document.getElementById('addBlock').innerHTML += blocks;
			}
		} else {
			if (document.getElementById('tab_addBlock')) {
				document.getElementById('tab_addBlock').style.display = 'none';
			}
		}
	}

	//Add classname to draghandles
	if (window.main && main.portal && main.portal.strings && main.portal.strings['move']) {
		var liTags = d.getElementsByClassName('section', 'li', 'region-main');
		var strMove = main.portal.strings['move'];
		for (var x=0; x<liTags.length; x++) {
            var strMoveAlt = strMove+' section '+x;
			var aTags = liTags[x].getElementsByTagName('a'); // IE sems to have a different title for the move icon under the folder.
			for (var y=0; y<aTags.length; y++) {
				//Add class to drag handles
				if (aTags[y].getAttribute('title')==strMove || aTags[y].getAttribute('title')== strMoveAlt) {
					d.addClass(aTags[y], 'draghandle');
					var ancs = d.getAncestorByTagName(aTags[y], 'li');
					if (d.hasClass(ancs, 'activity')) {
						d.addClass(aTags[y], 'dragactivity');
						d.insertBefore(aTags[y], ancs.firstChild);
						//d.insertBefore(aTags[y], aTags[y].parentNode.parentNode.firstChild);
					} else if (d.hasClass(ancs, 'section')) {
						d.addClass(aTags[y], 'dragsection');
					}
				}
			}
		}
	}

	var defsub = '0000000000000000000000000000000000000000';
	format_folderview_vars.cookie = YAHOO.util.Cookie.getSub(format_folderview_vars.cookiename, format_folderview_vars.subname);
	if (format_folderview_vars.cookie==null) { format_folderview_vars.cookie = defsub; }
	M.format_folderview.loadToggles();
	M.format_folderview.refreshToggleState(true);

	if (document.location.search.indexOf('subtitle=')!=-1) {
		format_folderview_vars.toggles[0] = (document.location.search.indexOf('subtitle=1')!=-1)?1:0;
		M.format_folderview.saveToggles();
	}

	if (format_folderview_vars.toggles[0]==0) {
		d.addClass(d.getElementsByClassName('sectionsubtitle'), 'hide');
		d.addClass(d.getElementsByClassName('pagesubtitle'), 'hide');
	}

	//If a hash to a section was passed make sure to expand it
	if (document.location.hash.indexOf('#section-')==0) {
		var t = document.location.hash.replace('#section-', '');
		M.format_folderview.expandSection(t);
	} else if (!d.hasClass(document.body, 'editing') && marknum>0 && document.location.search.indexOf('topic=')==-1) {
		M.format_folderview.expandSection(marknum);
		try { window.scrollTo(0, d.getY('section-'+marknum)); } catch(e) { }
		//document.location.hash = "#section-"+marknum;
	}

};

