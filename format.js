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

// Javascript functions for Folderview course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="folderview">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node: 'ul',
        container_class: 'folderview',
        section_node: 'li',
        section_class: 'section'
    };
};

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT: 'course-content',
        SECTIONADDMENUS: 'section_add_menus'
};

    var sectionlist = Y.Node.all('.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y));
    // Swap menus
    sectionlist.item(node1).one('.' + CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.' + CSS.SECTIONADDMENUS));
};

/**
 * Process sections after ajax response
 *
 * @param {YUI} Y YUI3 instance
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 * @return void
 */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    var CSS = {
        SECTIONNAME: 'sectionname',
        ONESECTIONIMAGE: '.right.side .icon.one'
    };

    if (response.action == 'move') {
        // update titles in all affected sections
        for (var i = sectionfrom; i <= sectionto; i++) {
            sectionlist.item(i).one('.' + CSS.SECTIONNAME).setContent(response.sectiontitles[i]);
            var url = sectionlist.item(i).one('.' + CSS.SECTIONNAME + ' a').get('href');
            var onesectionimg = sectionlist.item(i).one(CSS.ONESECTIONIMAGE);
            if (url && onesectionimg) {
                var onesectionlink = onesectionimg.get('parentNode');
                if (onesectionlink && onesectionlink.get('nodeName').toLowerCase() === 'a') {
                    onesectionlink.set('href', url);
                }
            }
        }
    }
};