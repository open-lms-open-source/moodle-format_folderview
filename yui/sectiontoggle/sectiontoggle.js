YUI.add('moodle-format_folderview-sectiontoggle', function(Y) {
        var CSS = {
            MAINWRAPPER: '.course-content ul.folderview',
            ALLSECTIONS: '.course-content ul.folderview li.section',
            SECTION: 'li.section',
            SECTIONACTIVITIES: 'ul.section',
            SECTIONSUMMARY: '.summary',
            SECTIONNAME: '.sectionname',
            TOGGLETARGET: '.left.side a[role=button]',
            EXPANDALL: '#topiclinktop .expand-sections',
            COLLAPSEALL: '#topiclinktop .collapse-sections'
        };

        var SECTIONTOGGLENAME = 'format_folderview_sectiontoggle';

        var SECTIONTOGGLE = function() {
            SECTIONTOGGLE.superclass.constructor.apply(this, arguments);
        };

        Y.extend(SECTIONTOGGLE, Y.Base, {
                /**
                 * Holds Expand string
                 */
                expandStr: '',

                /**
                 * Holds Collapse string
                 */
                collapseStr: '',

                /**
                 * Holds section activity list IDs
                 */
                sectionActivitiesIds: [],

                initializer: function(config) {
                    this.log(config);

                    this.expandStr = M.util.get_string('expand', 'format_folderview');
                    this.collapseStr = M.util.get_string('collapse', 'format_folderview');

                    var sections = Y.all(CSS.ALLSECTIONS);

                    // Initialize aria
                    this.init_aria_attributes(sections);

                    // Expand sections that need it
                    Y.Array.forEach(this.get('expandedsections'), function(expandedsection) {
                        this.toggle_section_classes(Y.one('#section-' + expandedsection))
                    }, this);

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
                    sections.each(function(node) {
                        var sectionnum = this.get_section_number(node);
                        if (sectionnum == 0) {
                            return;
                        }
                        var sectionActivities = node.one(CSS.SECTIONACTIVITIES);
                        var sectionName = node.one(CSS.SECTIONNAME);
                        var control = node.one(CSS.TOGGLETARGET);

                        sectionName.setAttribute('aria-hidden', 'true');
                        sectionName.set('id', 'sectionname_' + sectionnum);
                        if (sectionActivities) {
                            sectionActivities.set('id', 'sectioncontent_' + sectionnum);
                            sectionActivities.setAttribute('tabindex', '-1');
                            sectionActivities.setAttribute('role', 'region');
                            sectionActivities.setAttribute('aria-expanded', 'false');
                            sectionActivities.setAttribute('aria-labelledby', sectionName.get('id'));
                            control.setAttribute('aria-controls', sectionActivities.get('id'));
                            this.register_activity_region(sectionActivities.get('id'));
                        }
                    }, this);
                },

                /**
                 * Updates aria-controls on the expand/collapse all buttons
                 * @param id
                 */
                register_activity_region: function(id) {
                    var collapseNode = Y.one(CSS.COLLAPSEALL);
                    var expandNode = Y.one(CSS.EXPANDALL);

                    this.sectionActivitiesIds.push(id);
                    var idCSV = this.sectionActivitiesIds.join(',');
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
                        var sectionActivities = node.one(CSS.SECTIONACTIVITIES);
                        if (sectionActivities) {
                            sectionActivities.focus();
                        }
                    }
                },

                /**
                 * Event handler for when a user clicks on a section folder
                 * @param e
                 */
                handle_section_toggle: function(e) {
                    e.preventDefault();
                    var section = e.target.ancestor(CSS.SECTION);
                    this.toggle_section_classes(section);
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
                        if (!node.hasClass('expanded')) {
                            this.toggle_section_classes(node);
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
                        if (node.hasClass('expanded')) {
                            this.toggle_section_classes(node);
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
                            this.toggle_section_classes(section);
                            this.save_expanded_sections();
                        } else {
                            // Bug fix - this element can be created dynamically when
                            // the section is empty, add our precious stuffs...
                            var activities = section.one(CSS.SECTIONACTIVITIES);
                            if (activities && !activities.hasClass('expanded')) {
                                var sectionnum  = this.get_section_number(section);
                                var sectionName = section.one(CSS.SECTIONNAME);
                                var control = section.one(CSS.TOGGLETARGET);

                                activities.addClass('expanded');
                                activities.set('id', 'sectioncontent_' + sectionnum);
                                activities.setAttribute('tabindex', '-1');
                                activities.setAttribute('role', 'region');
                                activities.setAttribute('aria-expanded', 'true');
                                activities.setAttribute('aria-labelledby', sectionName.get('id'));
                                control.setAttribute('aria-controls', activities.get('id'));
                                this.register_activity_region(activities.get('id'));
                            }
                        }
                    }
                },

                /**
                 * Toggles section classes for when a section is expanded or not
                 * @param node
                 */
                toggle_section_classes: function(node) {
                    if (node) {
                        var sectionnum = this.get_section_number(node);
                        if (sectionnum == 0) {
                            return;
                        }
                        var addClass = true;
                        var nodeList = [node, node.one(CSS.SECTIONACTIVITIES), node.one(CSS.SECTIONSUMMARY)];
                        var link = node.one(CSS.TOGGLETARGET);
                        var span = link.one('span');
                        var fromStr = this.expandStr;
                        var toStr = this.collapseStr;

                        // Only trust section's class
                        if (node.hasClass('expanded')) {
                            addClass = false;
                            fromStr = this.collapseStr;
                            toStr = this.expandStr;
                        }
                        // Update help text
                        link.setAttribute('title', link.getAttribute('title').replace(fromStr, toStr));
                        span.set('innerHTML', span.get('innerHTML').replace(fromStr, toStr));

                        for (var i = 0; i < nodeList.length; i++) {
                            var aNode = nodeList[i];
                            if (!aNode) {
                                continue;
                            }
                            if (addClass) {
                                aNode.addClass('expanded');
                                if (aNode.test(CSS.SECTIONACTIVITIES)) {
                                    aNode.setAttribute('aria-expanded', 'true');
                                }
                            } else {
                                aNode.removeClass('expanded');
                                if (aNode.test(CSS.SECTIONACTIVITIES)) {
                                    aNode.setAttribute('aria-expanded', 'false');
                                }
                            }
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
        requires: ['base', 'event', 'io']
    }
);
