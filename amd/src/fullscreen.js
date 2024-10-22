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
 *
 * @author  2021 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-unused-vars */
/* eslint-disable no-console */

define([
        'jquery',
        'core/str',
        'core/ajax',
        'core/templates'
    ], function($, Str, Ajax, Templates) {
        "use strict";
        /* eslint-disable no-console */
        /**
         * @constructor
         */
        function Fullscreen() {
             $('#toggleFullscreen').on('click', this.toggleFullScreen.bind(this));
             document.addEventListener('fullscreenchange', this.changeFullScreen, false);
             document.addEventListener('mozfullscreenchange', this.changeFullScreen, false);
             document.addEventListener('MSFullscreenChange', this.changeFullScreen, false);
             document.addEventListener('webkitfullscreenchange', this.changeFullScreen, false);
        }

        Fullscreen.prototype.changeFullScreen = function(e) {
            let btnToggle = document.getElementById('toggleFullscreen');
            let page = document.getElementById('exescormpage');

            if (page.classList.contains('fullscreen')) {
                btnToggle.classList.remove('actived');
                page.classList.remove('fullscreen');
            } else {
                btnToggle.classList.add('actived');
                page.classList.add('fullscreen');
            }
        };

        Fullscreen.prototype.toggleFullScreen = function(e) {

            if (document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            } else {
                var element = $('#exescormpage')[0];
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            }

        };

        /** @type {jQuery} The jQuery node for the region. */
        Fullscreen.prototype.node = null;

        return {
            /**
             * @return {Fullscreen}
             */
            init: function() {
                return new Fullscreen();
            }
        };
    }
);
