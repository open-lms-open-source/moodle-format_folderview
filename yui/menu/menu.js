YUI.add('moodle-format_folderview-menu', function(Y) {
        var CSS = {
            TABSWRAPPER: '#menuPanelTabs',
            MAINWRAPPER: '#menuPanel',
            CLOSEICON: '#menuPanelClose',
            FOCUS: '.focusonme',
            TABS: 'span.tab a',
            ADDRESOURCE: 'li.section .right .add',
            SECTIONROOT: 'ul.folderview',
            ADDRESOURCESELECTOR: '#selAddToSection',
            ADDRESOURCETAB: '#tab_addResource',
            ADDRESOURCEDIALOG: '#addResource',
            ADDRESOURCELINK: '.restype a',
            ADDRESOURCEHIDDEN: '#addResourceHidden'
        };

        var MENUNAME = 'format_folderview_menu';

        var MENU = function() {
            MENU.superclass.constructor.apply(this, arguments);
        };

        Y.extend(MENU, Y.Base, {
                initializer: function(config) {
                    var wrapperNode = Y.one(CSS.TABSWRAPPER);
                    if (wrapperNode) {
                        wrapperNode.delegate('click', this.handle_tab_click, CSS.TABS, this);
                    }
                    var closeNode = Y.one(CSS.CLOSEICON);
                    if (closeNode) {
                        closeNode.on('click', this.hide_menu_panel, this);
                    }
                    var rootNode = Y.one(CSS.SECTIONROOT);
                    if (rootNode) {
                        rootNode.delegate('click', this.handle_show_add_resource, CSS.ADDRESOURCE, this);
                    }
                    var resourceNode = Y.one(CSS.ADDRESOURCEDIALOG);
                    if (resourceNode) {
                        resourceNode.delegate('click', this.handle_add_resource, CSS.ADDRESOURCELINK, this);
                    }
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
                 * Handle section widget add resource click
                 * @param e
                 */
                handle_show_add_resource: function(e) {
                    var section = e.target.ancestor('li.section');
                    this.show_menu_panel(Y.one(CSS.ADDRESOURCETAB));
                    Y.one(CSS.ADDRESOURCESELECTOR).set('value', this.get_section_number(section));
                },

                /**
                 * Handle adding an actual resource from the "Add Resource" dialog
                 * @param e
                 */
                handle_add_resource: function(e) {
                    e.preventDefault();

                    Y.one(CSS.ADDRESOURCEHIDDEN).set(
                        'value',
                        e.target.get('id').replace('add_mod_', '')
                    );
                    e.target.ancestor('form').submit();
                },

                /**
                 * Handle tab click
                 * @param e
                 */
                handle_tab_click: function(e) {
                    var node;
                    if (e.target.test('span')) {
                        node = e.target;
                    } else {
                        node = e.target.ancestor('span');
                    }
                    if (!node.hasClass('nodialog')) {
                        e.preventDefault();
                        this.show_menu_panel(node);
                    }
                },

                /**
                 * Show a tab/panel
                 * @param node
                 */
                show_menu_panel: function(node) {
                    this.hide_menu_panel();

                    var id = node.get('id').replace('tab_', '');
                    Y.one(CSS.MAINWRAPPER).set('className', 'dlg_' + id);
                    Y.one('body').addClass(id.toLowerCase());

                    var focusNode = Y.one('#' + id + ' ' + CSS.FOCUS);
                    if (focusNode) {
                        focusNode.focus();
                    }
                },

                /**
                 * Hide all tabs/panels
                 */
                hide_menu_panel: function() {
                    var node = Y.one(CSS.MAINWRAPPER);
                    Y.one('body').removeClass(node.get('className').replace('dlg_', '').toLowerCase());
                    node.set('className', 'nodialog');
                }
            },
            {
                NAME: MENUNAME,
                ATTRS: {
                }
            });
        M.format_folderview = M.format_folderview || {};
        M.format_folderview.init_menu = function(config) {
            return new MENU(config);
        }
    },
    '@VERSION@', {
        requires: ['base', 'event']
    }
);