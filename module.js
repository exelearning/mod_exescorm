// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript helper function for EXESCORM module.
 *
 * @package   mod-exescorm
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

mod_exescorm_launch_next_sco = null;
mod_exescorm_launch_prev_sco = null;
mod_exescorm_activate_item = null;
mod_exescorm_parse_toc_tree = null;
exescorm_layout_widget = null;

window.exescorm_current_node = null;

function underscore(str) {
    str = String(str).replace(/.N/g,".");
    return str.replace(/\./g,"__");
}

M.mod_exescorm = {};

M.mod_exescorm.init = function(Y, nav_display, navposition_left, navposition_top, hide_toc, collapsetocwinsize, toc_title, window_name, launch_sco, scoes_nav) {
    var exescorm_disable_toc = false;
    var exescorm_hide_nav = true;
    var exescorm_hide_toc = true;
    var launch_sco_fix = launch_sco;
    if (hide_toc == 0) {
        if (nav_display !== 0) {
            exescorm_hide_nav = false;
        }
        exescorm_hide_toc = false;
    } else if (hide_toc == 3) {
        exescorm_disable_toc = true;
    }

    scoes_nav = Y.JSON.parse(scoes_nav);

    var exescorm_update_siblings = function (scoesnav) {
        for(var key in scoesnav ){
            var siblings = [],
                parentscoid = key;
            for (var mk in scoesnav) {
                var val = scoesnav[mk];
                if (typeof val !== "undefined" && typeof val.parentscoid !== 'undefined' && val.parentscoid === parentscoid) {
                    siblings.push(mk);
                }
            }
            if (siblings.length > 1) {
                scoesnav = exescorm_get_siblings(scoesnav, siblings);
            }
        }
        return scoesnav;
    };

    var exescorm_get_siblings = function (scoesnav, siblings) {
        siblings.forEach(function (key, index) {
            if (index > 0 && typeof scoesnav[key] !== "undefined" && typeof scoesnav[key].prevsibling === "undefined") {
                scoesnav[key].prevsibling = siblings[index - 1];
            }
            if (index < siblings.length - 1 && typeof scoesnav[key] !== "undefined" &&
               typeof scoesnav[key].nextsibling === "undefined") {
                scoesnav[key].nextsibling = siblings[index + 1];
            }
        });
        return scoesnav;
    };

    scoes_nav = exescorm_update_siblings(scoes_nav);
    var exescorm_buttons = [];
    var exescorm_bloody_labelclick = false;
    var exescorm_nav_panel;

    Y.use('button', 'dd-plugin', 'panel', 'resize', 'gallery-sm-treeview', function(Y) {

        Y.TreeView.prototype.getNodeByAttribute = function(attribute, value) {
            var node = null,
                domnode = Y.one('a[' + attribute + '="' + value + '"]');
            if (domnode !== null) {
                node = exescorm_tree_node.getNodeById(domnode.ancestor('li').get('id'));
            }
            return node;
        };

        Y.TreeView.prototype.openAll = function () {
            this.get('container').all('.yui3-treeview-can-have-children').each(function(target) {
                this.getNodeById(target.get('id')).open();
            }, this);
        };

        Y.TreeView.prototype.closeAll = function () {
            this.get('container').all('.yui3-treeview-can-have-children').each(function(target) {
                this.getNodeById(target.get('id')).close();
            }, this);
        }

        var exescorm_parse_toc_tree = function(srcNode) {
            var SELECTORS = {
                    child: '> li',
                    label: '> li, > a',
                    textlabel : '> li, > span',
                    subtree: '> ul, > li'
                },
                children = [];

            srcNode.all(SELECTORS.child).each(function(childNode) {
                var child = {},
                    labelNode = childNode.one(SELECTORS.label),
                    textNode = childNode.one(SELECTORS.textlabel),
                    subTreeNode = childNode.one(SELECTORS.subtree);

                if (labelNode) {
                    var title = labelNode.getAttribute('title');
                    var scoid = labelNode.getData('scoid');
                    child.label = labelNode.get('outerHTML');
                    // Will be good to change to url instead of title.
                    if (title && title !== '#') {
                        child.title = title;
                    }
                    if (typeof scoid !== 'undefined') {
                        child.scoid = scoid;
                    }
                } else if (textNode) {
                    // The selector did not find a label node with anchor.
                    child.label = textNode.get('outerHTML');
                }

                if (subTreeNode) {
                    child.children = exescorm_parse_toc_tree(subTreeNode);
                }

                children.push(child);
            });

            return children;
        };

        mod_exescorm_parse_toc_tree = exescorm_parse_toc_tree;

        var exescorm_activate_item = function(node) {
            if (!node) {
                return;
            }
            // Check if the item is already active, avoid recursive calls.
            var content = Y.one('#exescorm_content');
            var old = Y.one('#exescorm_object');
            if (old) {
                var exescorm_active_url = Y.one('#exescorm_object').getAttribute('src');
                var node_full_url = M.cfg.wwwroot + '/mod/exescorm/loadSCO.php?' + node.title;
                if (node_full_url === exescorm_active_url) {
                    return;
                }
                // Start to unload iframe here
                if(!window_name){
                    content.removeChild(old);
                    old = null;
                }
            }
            // End of - Avoid recursive calls.

            exescorm_current_node = node;
            if (!exescorm_current_node.state.selected) {
                exescorm_current_node.select();
            }

            exescorm_tree_node.closeAll();
            var url_prefix = M.cfg.wwwroot + '/mod/exescorm/loadSCO.php?';
            var el_old_api = document.getElementById('exescormapi123');
            if (el_old_api) {
                el_old_api.parentNode.removeChild(el_old_api);
            }

            var obj = document.createElement('iframe');
            obj.setAttribute('id', 'exescorm_object');
            obj.setAttribute('type', 'text/html');
            obj.setAttribute('allowfullscreen', 'allowfullscreen');
            obj.setAttribute('webkitallowfullscreen', 'webkitallowfullscreen');
            obj.setAttribute('mozallowfullscreen', 'mozallowfullscreen');
            obj.setAttribute('onload', "exescorm_iframe_onload(this)");

            if (!window_name && node.title != null) {
                obj.setAttribute('src', url_prefix + node.title);
            }
            // Attach unload observers to the iframe. The exescorm package may be observing these unload events
            // and trying to save progress when they occur. We need to ensure we use the Beacon API in those
            // situations.
            if (typeof mod_exescorm_monitorForBeaconRequirement !== 'undefined') {
                mod_exescorm_monitorForBeaconRequirement(obj);
            }
            if (window_name) {
                var mine = window.open('','','width=1,height=1,left=0,top=0,scrollbars=no');
                if(! mine) {
                    alert(M.util.get_string('popupsblocked', 'exescorm'));
                }
                mine.close();
            }

            if (old) {
                if(window_name) {
                    var cwidth = exescormplayerdata.cwidth;
                    var cheight = exescormplayerdata.cheight;
                    var poptions = exescormplayerdata.popupoptions;
                    poptions = poptions + ',resizable=yes'; // Added for IE (MDL-32506).
                    exescorm_openpopup(M.cfg.wwwroot + "/mod/exescorm/loadSCO.php?" + node.title, window_name, poptions, cwidth, cheight);
                }
            } else {
                content.prepend(obj);
            }

            if (exescorm_hide_nav == false) {
                if (nav_display === 1 && navposition_left > 0 && navposition_top > 0) {
                    Y.one('#exescorm_object').addClass(cssclasses.exescorm_nav_under_content);
                }
                exescorm_fixnav();
            }
            exescorm_tree_node.openAll();
        };

        mod_exescorm_activate_item = exescorm_activate_item;

        /**
         * Enables/disables navigation buttons as needed.
         * @return void
         */
        var exescorm_fixnav = function() {
            launch_sco_fix = launch_sco;
            var skipprevnode = exescorm_skipprev(exescorm_current_node);
            var prevnode = exescorm_prev(exescorm_current_node);
            var upnode = exescorm_up(exescorm_current_node);
            var nextnode = exescorm_next(exescorm_current_node, true, true);
            var skipnextnode = exescorm_skipnext(exescorm_current_node, true, true);

            exescorm_buttons[0].set('disabled', ((skipprevnode === null) ||
                        (typeof(skipprevnode.scoid) === 'undefined') ||
                        (scoes_nav[skipprevnode.scoid].isvisible === "false") ||
                        (skipprevnode.title === null) ||
                        (scoes_nav[launch_sco].hideprevious === 1)));

            exescorm_buttons[1].set('disabled', ((prevnode === null) ||
                        (typeof(prevnode.scoid) === 'undefined') ||
                        (scoes_nav[prevnode.scoid].isvisible === "false") ||
                        (prevnode.title === null) ||
                        (scoes_nav[launch_sco].hideprevious === 1)));

            exescorm_buttons[2].set('disabled', (upnode === null) ||
                        (typeof(upnode.scoid) === 'undefined') ||
                        (scoes_nav[upnode.scoid].isvisible === "false") ||
                        (upnode.title === null));

            exescorm_buttons[3].set('disabled', ((nextnode === null) ||
                        ((nextnode.title === null) && (scoes_nav[launch_sco].flow !== 1)) ||
                        (typeof(nextnode.scoid) === 'undefined') ||
                        (scoes_nav[nextnode.scoid].isvisible === "false") ||
                        (scoes_nav[launch_sco].hidecontinue === 1)));

            exescorm_buttons[4].set('disabled', ((skipnextnode === null) ||
                        (skipnextnode.title === null) ||
                        (typeof(skipnextnode.scoid) === 'undefined') ||
                        (scoes_nav[skipnextnode.scoid].isvisible === "false") ||
                        scoes_nav[launch_sco].hidecontinue === 1));
        };

        var exescorm_toggle_toc = function(windowresize) {
            var toc = Y.one('#exescorm_toc');
            var exescorm_content = Y.one('#exescorm_content');
            var exescorm_toc_toggle_btn = Y.one('#exescorm_toc_toggle_btn');
            var toc_disabled = toc.hasClass('disabled');
            var disabled_by = toc.getAttribute('disabled-by');
            // Remove width element style from resize handle.
            toc.setStyle('width', null);
            exescorm_content.setStyle('width', null);
            if (windowresize === true) {
                if (disabled_by === 'user') {
                    return;
                }
                var body = Y.one('body');
                if (body.get('winWidth') < collapsetocwinsize) {
                    toc.addClass(cssclasses.disabled)
                        .setAttribute('disabled-by', 'screen-size');
                    exescorm_toc_toggle_btn.setHTML('&gt;').addClass('collapsed').removeClass('uncollapsed')
                        .set('title', M.util.get_string('show', 'moodle'));
                    exescorm_content.removeClass(cssclasses.exescorm_grid_content_toc_visible)
                        .addClass(cssclasses.exescorm_grid_content_toc_hidden);
                } else if (body.get('winWidth') > collapsetocwinsize) {
                    toc.removeClass(cssclasses.disabled)
                        .removeAttribute('disabled-by');
                    exescorm_toc_toggle_btn.setHTML('&lt;').addClass('uncollapsed').removeClass('collapsed')
                        .set('title', M.util.get_string('hide', 'moodle'));
                    exescorm_content.removeClass(cssclasses.exescorm_grid_content_toc_hidden)
                        .addClass(cssclasses.exescorm_grid_content_toc_visible);
                }
                return;
            }
            if (toc_disabled) {
                toc.removeClass(cssclasses.disabled)
                    .removeAttribute('disabled-by');
                exescorm_toc_toggle_btn.setHTML('&lt;').addClass('uncollapsed').removeClass('collapsed')
                    .set('title', M.util.get_string('hide', 'moodle'));
                exescorm_content.removeClass(cssclasses.exescorm_grid_content_toc_hidden)
                    .addClass(cssclasses.exescorm_grid_content_toc_visible);
            } else {
                toc.addClass(cssclasses.disabled)
                    .setAttribute('disabled-by', 'user');
                exescorm_toc_toggle_btn.setHTML('&gt;').addClass('collapsed').removeClass('uncollapsed')
                    .set('title', M.util.get_string('show', 'moodle'));
                exescorm_content.removeClass(cssclasses.exescorm_grid_content_toc_visible)
                    .addClass(cssclasses.exescorm_grid_content_toc_hidden);
            }
        };

        var exescorm_resize_layout = function() {
            if (window_name) {
                return;
            }

            // make sure that the max width of the TOC doesn't go to far

            var exescorm_toc_node = Y.one('#exescorm_toc');
            var maxwidth = parseInt(Y.one('#exescorm_layout').getComputedStyle('width'), 10);
            exescorm_toc_node.setStyle('maxWidth', (maxwidth - 200));
            var cwidth = parseInt(exescorm_toc_node.getComputedStyle('width'), 10);
            if (cwidth > (maxwidth - 1)) {
                exescorm_toc_node.setStyle('width', (maxwidth - 50));
            }

            let layout = document.querySelector('#exescorm_layout');
            let iFrame = document.querySelector('#exescorm_object');
            if (iFrame.contentWindow.document.body && layout) {
                iFrame.style.height = (iFrame.contentWindow.document.body.scrollHeight + 50) + 'px';
                layout.style.height = (iFrame.contentWindow.document.body.scrollHeight + 50) + 'px';
            }
        };


        var exescorm_up = function(node, update_launch_sco) {
            if (node.parent && node.parent.parent && typeof scoes_nav[launch_sco].parentscoid !== 'undefined') {
                var parentscoid = scoes_nav[launch_sco].parentscoid;
                var parent = node.parent;
                if (parent.title !== scoes_nav[parentscoid].url) {
                    parent = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[parentscoid].url);
                    if (parent === null) {
                        parent = exescorm_tree_node.rootNode.children[0];
                        parent.title = scoes_nav[parentscoid].url;
                    }
                }
                if (update_launch_sco) {
                    launch_sco = parentscoid;
                }
                return parent;
            }
            return null;
        };

        var exescorm_lastchild = function(node) {
            if (node.children.length) {
                return exescorm_lastchild(node.children[node.children.length - 1]);
            } else {
                return node;
            }
        };

        var exescorm_prev = function(node, update_launch_sco) {
            if (node.previous() && node.previous().children.length &&
                    typeof scoes_nav[launch_sco].prevscoid !== 'undefined') {
                node = exescorm_lastchild(node.previous());
                if (node) {
                    var prevscoid = scoes_nav[launch_sco].prevscoid;
                    if (node.title !== scoes_nav[prevscoid].url) {
                        node = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[prevscoid].url);
                        if (node === null) {
                            node = exescorm_tree_node.rootNode.children[0];
                            node.title = scoes_nav[prevscoid].url;
                        }
                    }
                    if (update_launch_sco) {
                        launch_sco = prevscoid;
                    }
                    return node;
                } else {
                    return null;
                }
            }
            return exescorm_skipprev(node, update_launch_sco);
        };

        var exescorm_skipprev = function(node, update_launch_sco) {
            if (node.previous() && typeof scoes_nav[launch_sco].prevsibling !== 'undefined') {
                var prevsibling = scoes_nav[launch_sco].prevsibling;
                var previous = node.previous();
                var prevscoid = scoes_nav[launch_sco].prevscoid;
                if (previous.title !== scoes_nav[prevscoid].url) {
                    previous = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[prevsibling].url);
                    if (previous === null) {
                        previous = exescorm_tree_node.rootNode.children[0];
                        previous.title = scoes_nav[prevsibling].url;
                    }
                }
                if (update_launch_sco) {
                    launch_sco = prevsibling;
                }
                return previous;
            } else if (node.parent && node.parent.parent && typeof scoes_nav[launch_sco].parentscoid !== 'undefined') {
                var parentscoid = scoes_nav[launch_sco].parentscoid;
                var parent = node.parent;
                if (parent.title !== scoes_nav[parentscoid].url) {
                    parent = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[parentscoid].url);
                    if (parent === null) {
                        parent = exescorm_tree_node.rootNode.children[0];
                        parent.title = scoes_nav[parentscoid].url;
                    }
                }
                if (update_launch_sco) {
                    launch_sco = parentscoid;
                }
                return parent;
            }
            return null;
        };

        var exescorm_next = function(node, update_launch_sco, test) {
            if (node === false) {
                return exescorm_tree_node.children[0];
            }
            if (node.children.length && typeof scoes_nav[launch_sco_fix].nextscoid != 'undefined') {
                node = node.children[0];
                var nextscoid = scoes_nav[launch_sco_fix].nextscoid;
                if (node.title !== scoes_nav[nextscoid].url) {
                    node = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[nextscoid].url);
                    if (node === null) {
                        node = exescorm_tree_node.rootNode.children[0];
                        node.title = scoes_nav[nextscoid].url;
                    }
                }
                if (update_launch_sco) {
                    launch_sco_fix = nextscoid;
                    if (!test) {
                        launch_sco = launch_sco_fix;
                    }
                }
                return node;
            }
            return exescorm_skipnext(node, update_launch_sco, test);
        };

        var exescorm_skipnext = function(node, update_launch_sco, test) {
            var next = node.next();
            if (next && next.title && typeof scoes_nav[launch_sco_fix] !== 'undefined' &&
                        typeof scoes_nav[launch_sco_fix].nextsibling !== 'undefined') {
                var nextsibling = scoes_nav[launch_sco_fix].nextsibling;
                if (next.title !== scoes_nav[nextsibling].url) {
                    next = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[nextsibling].url);
                    if (next === null) {
                        next = exescorm_tree_node.rootNode.children[0];
                        next.title = scoes_nav[nextsibling].url;
                    }
                }
                if (update_launch_sco) {
                    launch_sco_fix = nextsibling;
                    if (!test) {
                        launch_sco = launch_sco_fix;
                    }
                }
                return next;
            } else if (node.parent && node.parent.parent && typeof scoes_nav[launch_sco_fix].parentscoid !== 'undefined') {
                var parentscoid = scoes_nav[launch_sco_fix].parentscoid;
                var parent = node.parent;
                if (parent.title !== scoes_nav[parentscoid].url) {
                    parent = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[parentscoid].url);
                    if (parent === null) {
                        parent = exescorm_tree_node.rootNode.children[0];
                    }
                }
                if (update_launch_sco) {
                    launch_sco_fix = parentscoid;
                    if (!test) {
                        launch_sco = launch_sco_fix;
                    }
                }
                return exescorm_skipnext(parent, update_launch_sco, test);
            }
            return null;
        };

        /**
         * Sends a request to the sequencing handler script on the server.
         * @param {string} datastring
         * @returns {string|boolean|*}
         */
        var exescorm_dorequest_sequencing = function(datastring) {
            var myRequest = NewHttpReq();
            var result = DoRequest(
                myRequest,
                M.cfg.wwwroot + '/mod/exescorm/datamodels/sequencinghandler.php?' + datastring,
                '',
                false
            );
            return result;
        };

        // Launch prev sco
        var exescorm_launch_prev_sco = function() {
            var result = null;
            if (scoes_nav[launch_sco].flow === 1) {
                var datastring = scoes_nav[launch_sco].url + '&function=exescorm_seq_flow&request=backward';
                result = exescorm_dorequest_sequencing(datastring);

                // Check the exescorm_ajax_result, it may be false.
                if (result === false) {
                    // Either the outcome was a failure, or we are unloading and simply just don't know
                    // what the outcome actually was.
                    result = {};
                } else {
                    result = Y.JSON.parse(result);
                }

                if (typeof result.nextactivity !== 'undefined' && typeof result.nextactivity.id !== 'undefined') {
                        var node = exescorm_prev(exescorm_tree_node.getSelectedNodes()[0]);
                        if (node == null) {
                            // Avoid use of TreeView for Navigation.
                            node = exescorm_tree_node.getSelectedNodes()[0];
                        }
                        if (node.title !== scoes_nav[result.nextactivity.id].url) {
                            node = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[result.nextactivity.id].url);
                            if (node === null) {
                                node = exescorm_tree_node.rootNode.children[0];
                                node.title = scoes_nav[result.nextactivity.id].url;
                            }
                        }
                        launch_sco = result.nextactivity.id;
                        exescorm_activate_item(node);
                        exescorm_fixnav();
                } else {
                        exescorm_activate_item(exescorm_prev(exescorm_tree_node.getSelectedNodes()[0], true));
                }
            } else {
                exescorm_activate_item(exescorm_prev(exescorm_tree_node.getSelectedNodes()[0], true));
            }
        };

        // Launch next sco
        var exescorm_launch_next_sco = function () {
            launch_sco_fix = launch_sco;
            var result = null;
            if (scoes_nav[launch_sco].flow === 1) {
                var datastring = scoes_nav[launch_sco].url + '&function=exescorm_seq_flow&request=forward';
                result = exescorm_dorequest_sequencing(datastring);

                // Check the exescorm_ajax_result, it may be false.
                if (result === false) {
                    // Either the outcome was a failure, or we are unloading and simply just don't know
                    // what the outcome actually was.
                    result = {};
                } else {
                    result = Y.JSON.parse(result);
                }

                if (typeof result.nextactivity !== 'undefined' && typeof result.nextactivity.id !== 'undefined') {
                    var node = exescorm_next(exescorm_tree_node.getSelectedNodes()[0]);
                    if (node === null) {
                        // Avoid use of TreeView for Navigation.
                        node = exescorm_tree_node.getSelectedNodes()[0];
                    }
                    node = exescorm_tree_node.getNodeByAttribute('title', scoes_nav[result.nextactivity.id].url);
                    if (node === null) {
                        node = exescorm_tree_node.rootNode.children[0];
                        node.title = scoes_nav[result.nextactivity.id].url;
                    }
                    launch_sco = result.nextactivity.id;
                    launch_sco_fix = launch_sco;
                    exescorm_activate_item(node);
                    exescorm_fixnav();
                } else {
                    exescorm_activate_item(exescorm_next(exescorm_tree_node.getSelectedNodes()[0], true, false));
                }
            } else {
                exescorm_activate_item(exescorm_next(exescorm_tree_node.getSelectedNodes()[0], true,false));
            }
        };

        mod_exescorm_launch_prev_sco = exescorm_launch_prev_sco;
        mod_exescorm_launch_next_sco = exescorm_launch_next_sco;

        var cssclasses = {
                // YUI grid class: use 100% of the available width to show only content, TOC hidden.
                exescorm_grid_content_toc_hidden: 'yui3-u-1',
                // YUI grid class: use 1/5 of the available width to show TOC.
                exescorm_grid_toc: 'yui3-u-1-5',
                // YUI grid class: use 1/24 of the available width to show TOC toggle button.
                exescorm_grid_toggle: 'yui3-u-1-24',
                // YUI grid class: use 3/4 of the available width to show content, TOC visible.
                exescorm_grid_content_toc_visible: 'yui3-u-3-4',
                // Reduce height of #exescorm_object to accomodate nav buttons under content.
                exescorm_nav_under_content: 'exescorm_nav_under_content',
                disabled: 'disabled'
            };
        // layout
        Y.one('#exescorm_toc_title').setHTML(toc_title);

        if (exescorm_disable_toc) {
            Y.one('#exescorm_toc').addClass(cssclasses.disabled);
            Y.one('#exescorm_toc_toggle').addClass(cssclasses.disabled);
            Y.one('#exescorm_content').addClass(cssclasses.exescorm_grid_content_toc_hidden);
        } else {
            Y.one('#exescorm_toc').addClass(cssclasses.exescorm_grid_toc);
            Y.one('#exescorm_toc_toggle').addClass(cssclasses.exescorm_grid_toggle);
            Y.one('#exescorm_toc_toggle_btn')
                .setHTML('&lt;')
                .setAttribute('title', M.util.get_string('hide', 'moodle'));
            Y.one('#exescorm_content').addClass(cssclasses.exescorm_grid_content_toc_visible);
            exescorm_toggle_toc(true);
        }

        // hide the TOC if that is the default
        if (!exescorm_disable_toc) {
            if (exescorm_hide_toc == true) {
                Y.one('#exescorm_toc').addClass(cssclasses.disabled);
                Y.one('#exescorm_toc_toggle_btn')
                    .setHTML('&gt;')
                    .setAttribute('title', M.util.get_string('show', 'moodle'));
                Y.one('#exescorm_content')
                    .removeClass(cssclasses.exescorm_grid_content_toc_visible)
                    .addClass(cssclasses.exescorm_grid_content_toc_hidden);
            }
        }

        // Basic initialization completed, show the elements.
        Y.one('#exescorm_toc').removeClass('loading');
        Y.one('#exescorm_toc_toggle').removeClass('loading');

        // TOC Resize handle.
        var layout_width = parseInt(Y.one('#exescorm_layout').getComputedStyle('width'), 10);
        var exescorm_resize_handle = new Y.Resize({
            node: '#exescorm_toc',
            handles: 'r',
            defMinWidth: 0.2 * layout_width
        });
        // TOC tree
        var toc_source = Y.one('#exescorm_tree > ul');
        var toc = exescorm_parse_toc_tree(toc_source);
        // Empty container after parsing toc.
        var el = document.getElementById('exescorm_tree');
        el.innerHTML = '';
        var tree = new Y.TreeView({
            container: '#exescorm_tree',
            nodes: toc,
            multiSelect: false,
            lazyRender: false
        });
        exescorm_tree_node = tree;
        // Trigger after instead of on, avoid recursive calls.
        tree.after('select', function(e) {
            var node = e.node;
            if (node.title == '' || node.title == null) {
                return; //this item has no navigation
            }

            // If item is already active, return; avoid recursive calls.
            if (obj = Y.one('#exescorm_object')) {
                var exescorm_active_url = obj.getAttribute('src');
                var node_full_url = M.cfg.wwwroot + '/mod/exescorm/loadSCO.php?' + node.title;
                if (node_full_url === exescorm_active_url) {
                    return;
                }
            } else if(exescorm_current_node == node){
                return;
            }

            // Update launch_sco.
            if (typeof node.scoid !== 'undefined') {
                launch_sco = node.scoid;
            }
            exescorm_activate_item(node);
            if (node.children.length) {
                exescorm_bloody_labelclick = true;
            }
        });
        if (!exescorm_disable_toc) {
            tree.on('close', function(e) {
                if (exescorm_bloody_labelclick) {
                    exescorm_bloody_labelclick = false;
                    return false;
                }
            });
            tree.subscribe('open', function(e) {
                if (exescorm_bloody_labelclick) {
                    exescorm_bloody_labelclick = false;
                    return false;
                }
            });
        }
        tree.render();
        tree.openAll();

        // On getting the window, always set the focus on the current item
        Y.one(Y.config.win).on('focus', function (e) {
            var current = exescorm_tree_node.getSelectedNodes()[0];
            var toc_disabled = Y.one('#exescorm_toc').hasClass('disabled');
            if (current.id && !toc_disabled) {
                Y.one('#' + current.id).focus();
            }
        });

        // navigation
        if (exescorm_hide_nav == false) {
            var navbuttonshtml = `<span id="exescorm_nav">
                                    <button id="nav_skipprev" class="bg-primary"
                                            title="${M.util.get_string('player:skipprev', 'mod_exescorm')}">
                                        <span class="sr-only">${M.util.get_string('player:skipprev', 'mod_exescorm')}</span>
                                    </button><button id="nav_prev" class="bg-primary"
                                            title="${M.util.get_string('player:prev', 'mod_exescorm')}">
                                        <span class="sr-only">${M.util.get_string('player:prev', 'mod_exescorm')}</span>
                                    </button><button id="nav_up" class="bg-primary"
                                            title="${M.util.get_string('player:up', 'mod_exescorm')}">
                                        <span class="sr-only">${M.util.get_string('player:up', 'mod_exescorm')}</span>
                                    </button><button id="nav_next" class="bg-primary"
                                            title="${M.util.get_string('player:next', 'mod_exescorm')}">
                                        <span class="sr-only">${M.util.get_string('player:next', 'mod_exescorm')}</span>
                                    </button><button id="nav_skipnext" class="bg-primary"
                                            title="${M.util.get_string('player:skipnext', 'mod_exescorm')}">
                                        <span class="sr-only">${M.util.get_string('player:skipnext', 'mod_exescorm')}</span>
                                    </button>
                                </span>`;
            if (nav_display === 1) {
                Y.one('#exescorm_navpanel').setHTML(navbuttonshtml);
            } else {
                // Nav panel is floating type.
                var navposition = null;
                if (navposition_left < 0 && navposition_top < 0) {
                    // Set default XY.
                    navposition = Y.one('#exescorm_toc').getXY();
                    navposition[1] += 200;
                } else {
                    // Set user defined XY.
                    navposition = [];
                    navposition[0] = parseInt(navposition_left, 10);
                    navposition[1] = parseInt(navposition_top, 10);
                }
                exescorm_nav_panel = new Y.Panel({
                    fillHeight: "body",
                    headerContent: M.util.get_string('navigation', 'exescorm'),
                    visible: true,
                    xy: navposition,
                    zIndex: 999
                });
                exescorm_nav_panel.set('bodyContent', navbuttonshtml);
                exescorm_nav_panel.removeButton('close');
                exescorm_nav_panel.plug(Y.Plugin.Drag, {handles: ['.yui3-widget-hd']});
                exescorm_nav_panel.render();
            }

            exescorm_buttons[0] = new Y.Button({
                srcNode: '#nav_skipprev',
                render: true,
                on: {
                        'click' : function(ev) {
                            exescorm_activate_item(exescorm_skipprev(exescorm_tree_node.getSelectedNodes()[0], true));
                        },
                        'keydown' : function(ev) {
                            if (ev.domEvent.keyCode === 13 || ev.domEvent.keyCode === 32) {
                                exescorm_activate_item(exescorm_skipprev(exescorm_tree_node.getSelectedNodes()[0], true));
                            }
                        }
                    }
            });
            exescorm_buttons[1] = new Y.Button({
                srcNode: '#nav_prev',
                render: true,
                on: {
                    'click' : function(ev) {
                        exescorm_launch_prev_sco();
                    },
                    'keydown' : function(ev) {
                        if (ev.domEvent.keyCode === 13 || ev.domEvent.keyCode === 32) {
                            exescorm_launch_prev_sco();
                        }
                    }
                }
            });
            exescorm_buttons[2] = new Y.Button({
                srcNode: '#nav_up',
                render: true,
                on: {
                    'click' : function(ev) {
                        exescorm_activate_item(exescorm_up(exescorm_tree_node.getSelectedNodes()[0], true));
                    },
                    'keydown' : function(ev) {
                        if (ev.domEvent.keyCode === 13 || ev.domEvent.keyCode === 32) {
                            exescorm_activate_item(exescorm_up(exescorm_tree_node.getSelectedNodes()[0], true));
                        }
                    }
                }
            });
            exescorm_buttons[3] = new Y.Button({
                srcNode: '#nav_next',
                render: true,
                on: {
                    'click' : function(ev) {
                        exescorm_launch_next_sco();
                    },
                    'keydown' : function(ev) {
                        if (ev.domEvent.keyCode === 13 || ev.domEvent.keyCode === 32) {
                            exescorm_launch_next_sco();
                        }
                    }
                }
            });
            exescorm_buttons[4] = new Y.Button({
                srcNode: '#nav_skipnext',
                render: true,
                on: {
                    'click' : function(ev) {
                        launch_sco_fix = launch_sco;
                        exescorm_activate_item(exescorm_skipnext(exescorm_tree_node.getSelectedNodes()[0], true, false));
                    },
                    'keydown' : function(ev) {
                        launch_sco_fix = launch_sco;
                        if (ev.domEvent.keyCode === 13 || ev.domEvent.keyCode === 32) {
                            exescorm_activate_item(exescorm_skipnext(exescorm_tree_node.getSelectedNodes()[0], true, false));
                        }
                    }
                }
            });
        }

        // finally activate the chosen item
        var exescorm_first_url = null;
        if (typeof tree.rootNode.children[0] !== 'undefined') {
            if (tree.rootNode.children[0].title !== scoes_nav[launch_sco].url) {
                var node = tree.getNodeByAttribute('title', scoes_nav[launch_sco].url);
                if (node !== null) {
                    exescorm_first_url = node;
                }
            } else {
                exescorm_first_url = tree.rootNode.children[0];
            }
        }

        if (exescorm_first_url == null) { // This is probably a single sco with no children (AICC Direct uses this).
            exescorm_first_url = tree.rootNode;
        }
        exescorm_first_url.title = scoes_nav[launch_sco].url;
        exescorm_activate_item(exescorm_first_url);

        // resizing
        exescorm_resize_layout();

        // Collapse/expand TOC.
        Y.one('#exescorm_toc_toggle').on('click', exescorm_toggle_toc);
        Y.one('#exescorm_toc_toggle').on('key', exescorm_toggle_toc, 'down:enter,32');
        // fix layout if window resized
        Y.on("windowresize", function() {
            exescorm_resize_layout();
            var toc_displayed = Y.one('#exescorm_toc').getComputedStyle('display') !== 'none';
            if ((!exescorm_disable_toc && !exescorm_hide_toc) || toc_displayed) {
                exescorm_toggle_toc(true);
            }
            // Set 20% as minWidth constrain of TOC.
            var layout_width = parseInt(Y.one('#exescorm_layout').getComputedStyle('width'), 10);
            exescorm_resize_handle.set('defMinWidth', 0.2 * layout_width);
        });
        // On resize drag, change width of exescorm_content.
        exescorm_resize_handle.on('resize:resize', function() {
            var tocwidth = parseInt(Y.one('#exescorm_toc').getComputedStyle('width'), 10);
            var layoutwidth = parseInt(Y.one('#exescorm_layout').getStyle('width'), 10);
            Y.one('#exescorm_content').setStyle('width', (layoutwidth - tocwidth - 60));
        });
    });
};

M.mod_exescorm.connectPrereqCallback = {

    success: function(id, o) {
        if (o.responseText !== undefined) {
            var snode = null,
                stitle = null;
            if (exescorm_tree_node && o.responseText) {
                snode = exescorm_tree_node.getSelectedNodes()[0];
                stitle = null;
                if (snode) {
                    stitle = snode.title;
                }
                // All gone with clear, add new root node.
                exescorm_tree_node.clear(exescorm_tree_node.createNode());
            }
            // Make sure the temporary tree element is not there.
            var el_old_tree = document.getElementById('exescormtree123');
            if (el_old_tree) {
                el_old_tree.parentNode.removeChild(el_old_tree);
            }
            var el_new_tree = document.createElement('div');
            var pagecontent = document.getElementById("page-content");
            if (!pagecontent) {
                pagecontent = document.getElementById("content");
            }
            if (!pagecontent) {
                pagecontent = document.getElementById("exescormpage");
            }
            el_new_tree.setAttribute('id','exescormtree123');
            el_new_tree.innerHTML = o.responseText;
            // Make sure it does not show.
            el_new_tree.style.display = 'none';
            pagecontent.appendChild(el_new_tree);
            // Ignore the first level element as this is the title.
            var startNode = el_new_tree.firstChild.firstChild;
            if (startNode.tagName == 'LI') {
                // Go back to the beginning.
                startNode = el_new_tree;
            }
            var toc_source = Y.one('#exescormtree123 > ul');
            var toc = mod_exescorm_parse_toc_tree(toc_source);
            exescorm_tree_node.appendNode(exescorm_tree_node.rootNode, toc);
            var el = document.getElementById('exescormtree123');
            el.parentNode.removeChild(el);
            exescorm_tree_node.render();
            exescorm_tree_node.openAll();
            if (stitle !== null) {
                snode = exescorm_tree_node.getNodeByAttribute('title', stitle);
                // Do not let destroyed node to be selected.
                if (snode && !snode.state.destroyed) {
                    snode.select();
                    var toc_disabled = Y.one('#exescorm_toc').hasClass('disabled');
                    if (!toc_disabled) {
                        if (!snode.state.selected) {
                            snode.select();
                        }
                    }
                }
            }
        }
    },

    failure: function(id, o) {
        // TODO: do some sort of error handling.
    }

};

/**
 * Resizes iFrame and container height to iframes body size.
 * This function is declared on windows namespace so iframe onload event can find it.
 *  Used as mutation observer callback.
 *
 */
var exescorm_resize = function() {
    let iFrame = document.querySelector('#exescorm_object');
    let layout = document.querySelector('#exescorm_layout');
    if (iFrame.contentWindow.document.body && layout) {
        iFrame.style.height = (iFrame.contentWindow.document.body.scrollHeight + 50) + 'px';
        layout.style.height = (iFrame.contentWindow.document.body.scrollHeight + 50) + 'px';
    }
};

/**
 * IFrame's onload handler. Used to keep iFrame's height dynamic, varying on iFrame's contents.
 *
 * @param {Element} iFrame
 */
var exescorm_iframe_onload = function(iFrame) {
    exescorm_resize([], null);
    // Set a mutation observer, so we can adapt to changes from iFrame's javascript (such
    // as tab clicks o hide/show sections).
    const config = {attributes: true, childList: true, subtree: true};
    const observer = new MutationObserver(exescorm_resize);
    observer.observe(iFrame.contentWindow.document.body, config);
};
