YUI.add('moodle-format_folderview-utility', function(Y) {
        var CSS = {
            EDITINGMOVESELECTOR: 'ul.folderview li.section li.activity .commands a.editing_move'
        };

        var UTILITYNAME = 'format_folderview_utility';

        var UTILITY = function() {
            UTILITY.superclass.constructor.apply(this, arguments);
        };

        Y.extend(UTILITY, Y.Base, {
                initializer: function(config) {
                    Y.all(CSS.EDITINGMOVESELECTOR).each(function(node) {
                        var parent = node.ancestor('li.activity');
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