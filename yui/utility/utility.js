YUI.add('moodle-format_folderview-utility', function(Y) {
        var CSS = {
            EDITINGMOVESELECTOR: 'ul.folderview li.section li.activity .commands .editing_move',
            ACTIVITY: 'li.activity',
            SECTIONS: 'ul.folderview li.section'
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
                        Y.once('available', function() {
                            callback();
                        }, CSS.EDITINGMOVESELECTOR, Y);
                    }, CSS.SECTIONS);
                },

                reposition_move_widgets: function() {
                    Y.all(CSS.EDITINGMOVESELECTOR).each(function(node) {
                        var parent = node.ancestor(CSS.ACTIVITY);
                        parent.insertBefore(node, parent.get('firstChild'));
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