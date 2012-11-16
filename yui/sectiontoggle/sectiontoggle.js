YUI.add('moodle-format_folderview-sectiontoggle', function(Y) {
        var CSS = {
            MAINWRAPPER: '.course-content ul.folderview',
            ALLSECTIONS: '.course-content ul.folderview li.section',
            SECTIONACTIVITIES: 'ul.section',
            SECTIONSUMMARY: '.summary',
            TOGGLETARGET: 'img.folder_icon',
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
                },

                /**
                 * Event handler for when a user clicks on a section folder
                 * @param e
                 */
                handle_section_toggle: function(e) {
                    e.preventDefault();
                    this.toggle_section_classes(e.target.ancestor('li.section'));
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
                 * Toggles section classes for when a section is expanded or not
                 * @param node
                 */
                toggle_section_classes: function(node) {
                    if (node) {
                        node.toggleClass('expanded');
                        var activities = node.one(CSS.SECTIONACTIVITIES);
                        var summary    = node.one(CSS.SECTIONSUMMARY);
                        if (activities) {
                            activities.toggleClass('expanded');
                        }
                        if (summary) {
                            summary.toggleClass('expanded');
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
