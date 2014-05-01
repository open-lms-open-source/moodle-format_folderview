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

/**
 * Folderview section collapse and expand
 *
 * @module moodle-format_folderview-sectiontoggle
 */

var CSS = {
    MAINWRAPPER: '.course-content ul.folderview',
    ALLSECTIONS: '.course-content ul.folderview li.section',
    SECTION: 'li.section',
    SECTIONACTIVITIES: 'ul.section',
    SECTIONCONTENT: '.content',
    SECTIONSUMMARY: '.summary',
    SECTIONNAME: '.sectionname',
    TOGGLETARGET: '.left.side .foldertoggle',
    EXPANDALL: '#topiclinktop .expand-sections',
    COLLAPSEALL: '#topiclinktop .collapse-sections'
};

/**
 * Expands and collapses course sections
 *
 * @constructor
 * @namespace M.format_folderview
 * @class SectionToggle
 * @extends Y.Base
 */
function SECTIONTOGGLE() {
    SECTIONTOGGLE.superclass.constructor.apply(this, arguments);
}

SECTIONTOGGLE.NAME = NAME;
SECTIONTOGGLE.ATTRS = {
    /**
     * Current course ID, used for AJAX requests
     *
     * @attribute courseid
     * @type Number
     * @required
     */
    courseid: {
        validator: Y.Lang.isNumber
    },

    /**
     * Current course ID, used for AJAX requests
     *
     * @attribute ajaxurl
     * @type String
     * @required
     */
    ajaxurl: {
        validator: Y.Lang.isString
    },

    /**
     * Currently expanded sections
     *
     * @attribute expandedsections
     * @type Array
     * @default []
     * @optional
     */
    expandedsections: {
        value: [],
        validator: Y.Lang.isArray
    },

    /**
     * Aria live log
     *
     * Used to announce actions to users.
     *
     * @attribute liveLog
     * @type M.local_mr.init_livelog
     * @default M.local_mr.init_livelog
     * @required
     */
    liveLog: {
        readOnly: true,
        valueFn: function() {
            return M.local_mr.init_livelog({});
        }
    }
};

Y.extend(SECTIONTOGGLE, Y.Base,
    {
        initializer: function() {
            var sections = Y.all(CSS.ALLSECTIONS);

            // Initialize aria
            this.init_aria_attributes(sections);

            // Setup our watcher for clicks
            var wrapperNode = Y.one(CSS.MAINWRAPPER);
            if (wrapperNode) {
                wrapperNode.delegate('click', this.handle_section_toggle, CSS.TOGGLETARGET, this);
            }
            var expandNode = Y.one(CSS.EXPANDALL);
            if (expandNode) {
                expandNode.on('click', this.handle_expand_all, this);
            }
            var collapseNode = Y.one(CSS.COLLAPSEALL);
            if (collapseNode) {
                collapseNode.on('click', this.handle_collapse_all, this);
            }
            if (!sections.isEmpty()) {
                sections.on('drop', this.handle_drop, this);
            }
        },

        /**
         * Sets up aria attributes for expand/collapse sections
         * @param sections
         */
        init_aria_attributes: function(sections) {
            if (sections.isEmpty()) {
                return;
            }
            var sectionContentIds = [];
            sections.each(function(node) {
                var sectionnum = this.get_section_number(node);
                if (sectionnum === 0) {
                    return;
                }
                var sectionContent = node.one(CSS.SECTIONCONTENT);
                var sectionName = node.one(CSS.SECTIONNAME);
                var control = node.one(CSS.TOGGLETARGET);

                if (Y.Lang.isNull(control)) {
                    return; // Missing control node, don't process.
                }
                if (control.local_mr_ariacontrol !== undefined) {
                    return; // Already wired up.
                }
                Y.log('Wiring up section number: ' + sectionnum, 'debug', SECTIONTOGGLE.NAME);

                sectionContent.plug(M.local_mr.ariacontrolled, {
                    ariaLabelledBy: sectionName,
                    ariaState: 'aria-expanded',
                    tabIndex: null,
                    autoHideShow: false,
                    autoFocus: false
                });
                control.plug(M.local_mr.ariacontrol, { ariaControls: sectionContent });

                // Need to run extra code after toggle
                control.local_mr_ariacontrol.on('afterToggle', function(e) {
                    this.toggle_section_classes(
                        e.target.get('host').ancestor(CSS.SECTION)
                    );
                }, this);

                // Expand the section if set in user pref
                if (Y.Array.indexOf(this.get('expandedsections'), sectionnum) !== -1) {
                    control.local_mr_ariacontrol.toggle_state();
                }
                sectionContentIds.push(sectionContent.generateID());
            }, this);

            this.init_aria_attributes_toggle_all(sectionContentIds);
        },

        /**
         * Updates aria-controls on the expand/collapse all buttons
         * @param sectionContentIds
         */
        init_aria_attributes_toggle_all: function(sectionContentIds) {
            var collapseNode = Y.one(CSS.COLLAPSEALL);
            var expandNode = Y.one(CSS.EXPANDALL);
            var idCSV = sectionContentIds.join(',');

            if (collapseNode) {
                collapseNode.setAttribute('aria-controls', idCSV);
            }
            if (expandNode) {
                expandNode.setAttribute('aria-controls', idCSV);
            }
        },

        /**
         * Event handler for when a user clicks on a section folder
         * @param e
         */
        handle_section_toggle: function(e) {
            e.preventDefault();

            var section = e.target.ancestor(CSS.SECTION);

            if (section) {
                var control = section.one(CSS.TOGGLETARGET);
                if (!Y.Lang.isNull(control) && control.local_mr_ariacontrol === undefined) {
                    // Section has somehow not been wired up, do it now.
                    this.init_aria_attributes(Y.all(CSS.ALLSECTIONS));

                    // We need to now toggle the state since it wasn't wired in the first place.
                    if (control.local_mr_ariacontrol !== undefined) {
                        control.local_mr_ariacontrol.toggle_state();
                    }
                }
            }
            if (section && section.hasClass('expanded')) {
                this.get('liveLog').log_text(M.str.format_folderview.topicexpanded);
            } else if (section && !section.hasClass('expanded')) {
                this.get('liveLog').log_text(M.str.format_folderview.topiccollapsed);
            }
            this.save_expanded_sections();
        },

        /**
         * Expand all sections
         * @param e
         */
        handle_expand_all: function(e) {
            e.preventDefault();
            Y.all(CSS.ALLSECTIONS).each(function(node) {
                if (!node.hasClass('expanded') && this.get_section_number(node) !== 0) {
                    node.one(CSS.TOGGLETARGET).local_mr_ariacontrol.toggle_state();
                }
            }, this);

            this.save_expanded_sections();
        },

        /**
         * Collapse all sections
         * @param e
         */
        handle_collapse_all: function(e) {
            e.preventDefault();
            Y.all(CSS.ALLSECTIONS).each(function(node) {
                if (node.hasClass('expanded') && this.get_section_number(node) !== 0) {
                    node.one(CSS.TOGGLETARGET).local_mr_ariacontrol.toggle_state();
                }
            }, this);

            this.save_expanded_sections();
        },

        /**
         * A file was dropped on a section, expand it
         * @param e
         */
        handle_drop: function(e) {
            var section = e.currentTarget;
            if (!section.test(CSS.SECTION)) {
                section = section.ancestor(CSS.SECTION);
            }
            if (section && this.get_section_number(section) !== 0) {
                if (!section.hasClass('expanded')) {
                    section.one(CSS.TOGGLETARGET).local_mr_ariacontrol.toggle_state();
                    this.save_expanded_sections();
                } else {
                    // Bug fix - this element can be created dynamically when
                    // the section is empty, add our precious class...
                    var activities = section.one(CSS.SECTIONACTIVITIES);
                    if (activities && !activities.hasClass('expanded')) {
                        activities.addClass('expanded');
                    }
                }
            }
        },

        /**
         * Toggles section classes for when a section is expanded or not
         * @param section
         */
        toggle_section_classes: function(section) {
            if (section) {
                var nodes = [section, section.one(CSS.SECTIONSUMMARY)];
                var sectionActivities = section.one(CSS.SECTIONACTIVITIES);
                if (sectionActivities) {
                    nodes.push(sectionActivities);
                }
                var nodeList = new Y.NodeList(nodes);

                // Only trust section's class
                if (section.hasClass('expanded')) {
                    nodeList.removeClass('expanded');
                } else {
                    nodeList.addClass('expanded');
                }
            } else {
                Y.log('Section node does not exist', 'debug', SECTIONTOGGLE.NAME);
            }
        },

        /**
         * Saves the expanded sections at endpoint
         */
        save_expanded_sections: function() {
            var expanded = [];

            Y.all('li.section.expanded').each(function(node) {
                expanded.push(this.get_section_number(node));
            }, this);

            Y.io(this.get('ajaxurl'), {
                context: this,
                method: 'POST',
                data: {
                    courseid: this.get('courseid'),
                    expandedsections: expanded.join(','),
                    action: 'setexpandedsections',
                    sesskey: M.cfg.sesskey
                },
                on: {
                    complete: function(id, response) {
                        Y.log('Updated expanded sections: ' + response.responseText, 'debug', SECTIONTOGGLE.NAME);
                    },
                    failure: function(id, response) {
                        Y.log(response, 'error', SECTIONTOGGLE.NAME);
                    }
                }
            });
        },

        /**
         * Given a section node, get the section number
         * @param node
         * @return {Number}
         */
        get_section_number: function(node) {
            return Number(node.get('id').replace(/section-/i, ''));
        }
    }
);

M.format_folderview = M.format_folderview || {};
M.format_folderview.SectionToggle = SECTIONTOGGLE;
M.format_folderview.init_sectiontoggle = function(config) {
    return new SECTIONTOGGLE(config);
};
