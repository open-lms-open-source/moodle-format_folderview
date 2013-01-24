YUI.add('moodle-format_folderview-sectiontoggle', function(Y) {
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

        var SECTIONTOGGLENAME = 'format_folderview_sectiontoggle';

        var SECTIONTOGGLE = function() {
            SECTIONTOGGLE.superclass.constructor.apply(this, arguments);
        };

        Y.extend(SECTIONTOGGLE, Y.Base, {
                initializer: function(config) {
                    this.log(config);

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
                        if (sectionnum == 0) {
                            return;
                        }
                        var sectionContent = node.one(CSS.SECTIONCONTENT);
                        var sectionName = node.one(CSS.SECTIONNAME);
                        var control = node.one(CSS.TOGGLETARGET);

                        sectionContent.plug(M.local_mr.ariacontrolled, {
                            ariaLabelledBy: sectionName,
                            ariaState: 'aria-expanded',
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
                 * Set the focus on the passed section
                 * @param node
                 */
                focus_on_section: function(node) {
                    if (node && node.hasClass('expanded')) {
                        node.one(CSS.SECTIONCONTENT).local_mr_ariacontrolled.focus();
                    }
                },

                /**
                 * Event handler for when a user clicks on a section folder
                 * @param e
                 */
                handle_section_toggle: function(e) {
                    e.preventDefault();
                    var section = e.target.ancestor(CSS.SECTION);
                    this.focus_on_section(section);
                    this.save_expanded_sections();
                },

                /**
                 * Expand all sections
                 * @param e
                 */
                handle_expand_all: function(e) {
                    e.preventDefault();
                    Y.all(CSS.ALLSECTIONS).each(function(node) {
                        if (!node.hasClass('expanded') && this.get_section_number(node) != 0) {
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
                        if (node.hasClass('expanded') && this.get_section_number(node) != 0) {
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
                    if (section) {
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
                        this.log('Section node does not exist');
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
                                this.log('Updated expanded sections: ' + response.responseText)
                            },
                            failure: function(id, response) {
                                this.log(response, 'error');
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
                },

                /**
                 * Log a message to the console
                 * @param message
                 * @param method
                 */
                log: function(message, method) {
                    if (M.cfg.developerdebug) {
                        if (method === undefined) {
                            method = 'log';
                        }
                        // Debug only...
                        // console[method](message);
                    }
                }
            },
            {
                NAME: SECTIONTOGGLENAME,
                ATTRS: {
                    courseid: {
                        value: null
                    },
                    ajaxurl: {
                        value: ''
                    },
                    expandedsections: {
                        'value': []
                    }
                }
            });
        M.format_folderview = M.format_folderview || {};
        M.format_folderview.init_sectiontoggle = function(config) {
            return new SECTIONTOGGLE(config);
        }
    },
    '@VERSION@', {
        requires: ['base', 'event', 'io', 'moodle-local_mr-ariacontrol', 'moodle-local_mr-ariacontrolled']
    }
);
