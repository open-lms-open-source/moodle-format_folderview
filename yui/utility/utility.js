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

YUI.add('moodle-format_folderview-utility', function(Y) {
        var CSS = {
            EDITINGMOVESELECTOR: 'ul.folderview li.section li.activity .commands .editing_move',
            ACTIVITY: 'li.activity',
            SECTIONS: 'ul.folderview li.section',
            MOD: '.mod-indent'
        };

        var UTILITYNAME = 'format_folderview_utility';

        var UTILITY = function() {
            UTILITY.superclass.constructor.apply(this, arguments);
        };

        Y.extend(UTILITY, Y.Base, {
                initializer: function(config) {
                    this.reposition_move_widgets();

                    // callback is a workaround - couldn't reliably pass
                    // to 'available' event handler
                    var callback = this.reposition_move_widgets;
                    Y.on('drop', function() {
                        var fixOnAvailable = function() {
                            Y.on('available', function() {
                                callback();

                                if (!Y.all('.dndupload-progress-outer').isEmpty()) {
                                    setTimeout(fixOnAvailable, 10);
                                }
                            }, CSS.EDITINGMOVESELECTOR, Y);
                        };
                        fixOnAvailable();

                    }, CSS.SECTIONS);
                },

                reposition_move_widgets: function() {
                    Y.all(CSS.EDITINGMOVESELECTOR).each(function(node) {
                        var parent = node.ancestor(CSS.ACTIVITY);
                        parent.insertBefore(node, parent.get('firstChild'));

                        // We add mod-indent-0 so our move icon can get some space.
                        var mod = parent.one(CSS.MOD);
                        if (mod.getAttribute('class').search('mod-indent-') === -1) {
                            mod.addClass('mod-indent-0');
                        }
                    });
                }
            },
            {
                NAME: UTILITYNAME,
                ATTRS: {
                }
            });
        M.format_folderview = M.format_folderview || {};
        M.format_folderview.init_utility = function(config) {
            return new UTILITY(config);
        }
    },
    '@VERSION@', {
        requires: ['base', 'event']
    }
);