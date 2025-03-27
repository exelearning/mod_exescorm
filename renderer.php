<?php
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
 * Defines the renderer for the exescorm module.
 *
 * @package mod_exescorm
 * @copyright 2013 Dan Marsden
 * @author Dan Marsden <dan@danmarsden.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_exescorm\exeonline\exescorm_redirector;

/**
 * The renderer for the exescorm module.
 *
 * @copyright 2013 Dan Marsden
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_exescorm_renderer extends plugin_renderer_base {
    public function view_user_heading($user, $course, $baseurl, $attempt, $attemptids) {
        $output = '';
        $output .= $this->box_start('generalbox boxaligncenter');
        $output .= html_writer::start_tag('div', ['class' => 'mdl-align']);
        $output .= $this->user_picture($user, ['courseid' => $course->id, 'link' => true]);
        $url = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
        $output .= html_writer::link($url, fullname($user));
        $baseurl->param('attempt', '');
        $pb = new mod_exescorm_attempt_bar($attemptids, $attempt, $baseurl, 'attempt');
        $output .= $this->render($pb);
        $output .= html_writer::end_tag('div');
        $output .= $this->box_end();
        return $output;
    }
    /**
     * exescorm attempt bar renderer
     *
     * @param mod_exescorm_attempt_bar $attemptbar
     * @return string
     */
    protected function render_mod_exescorm_attempt_bar(mod_exescorm_attempt_bar $attemptbar) {
        $output = '';
        $attemptbar = clone($attemptbar);
        $attemptbar->prepare($this, $this->page, $this->target);

        if (count($attemptbar->attemptids) > 1) {
            $output .= get_string('attempt', 'mod_exescorm') . ':';

            if (!empty($attemptbar->previouslink)) {
                $output .= '&#160;(' . $attemptbar->previouslink . ')&#160;';
            }

            foreach ($attemptbar->attemptlinks as $link) {
                $output .= "&#160;&#160;$link";
            }

            if (!empty($attemptbar->nextlink)) {
                $output .= '&#160;&#160;(' . $attemptbar->nextlink . ')';
            }
        }

        return html_writer::tag('div', $output, ['class' => 'paging']);
    }

    /**
     * Rendered HTML for the report action is provided.
     *
     * @param \mod_exescorm\output\actionbar $actionbar actionbar object.
     * @return bool|string rendered HTML for the report action.
     */
    public function report_actionbar(\mod_exescorm\output\actionbar $actionbar): string {
        return $this->render_from_template('mod_exescorm/report_actionbar', $actionbar->export_for_template($this));
    }

    /**
     * Rendered HTML for the user report action is provided
     *
     * @param \mod_exescorm\output\userreportsactionbar $userreportsactionbar userreportsactionbar object
     * @return string rendered HTML for the user report action.
     */
    public function user_report_actionbar(\mod_exescorm\output\userreportsactionbar $userreportsactionbar): string {
        return $this->render_from_template('mod_exescorm/user_report_actionbar',
                                            $userreportsactionbar->export_for_template($this));
    }

    /**
     * Generate the EXESCORM's "Exit activity" button
     *
     * @param string $url The url to be hooked up to the exit button
     * @param stdClass $cm The course module viewed.
     * @return string
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function generate_editexitbar(string $url, \stdClass $cm): string {
        $context = ['returnaction' => $url];
        if (has_capability('moodle/course:update', context_course::instance($cm->course))) {
            $returnto = new moodle_url("/mod/exescorm/view.php", ['id' => $cm->id, 'forceview' => 1]);
            $exeonlineurl = get_config('exescorm', 'exeonlinebaseuri');
            if (empty($exeonlineurl)) {
                $context['editaction'] = false;
            } else {
                $context['editaction'] = exescorm_redirector::get_redirection_url($cm->id, $returnto)->out(false);
            }
        }
        return $this->render_from_template('mod_exescorm/player_editexitbar', $context);
    }

    /**
     * Generate the EXESCORM's "togle full screen" button
     *
     * @return string
     */
    public function generate_tocbox_action_buttons(): string {
        $context = [];
        return $this->render_from_template('mod_exescorm/player_tocbox_action_buttons', $context);
    }
}

/**
 * Component representing a EXESCORM attempts bar.
 *
 * @copyright 2013 Dan Marsden
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_exescorm
 */
class mod_exescorm_attempt_bar implements renderable {

    /**
     * @var array An array of the attemptids
     */
    public $attemptids;

    /**
     * @var int The attempt you are currently viewing.
     */
    public $attempt;

    /**
     * @var string|moodle_url If this  is a string then it is the url which will be appended with $pagevar,
     * an equals sign and the attempt number.
     * If this is a moodle_url object then the pagevar param will be replaced by
     * the attempt no, for each attempt.
     */
    public $baseurl;

    /**
     * @var string This is the variable name that you use for the attempt in your
     * code (ie. 'tablepage', 'blogpage', etc)
     */
    public $pagevar;

    /**
     * @var string A HTML link representing the "previous" attempt.
     */
    public $previouslink = null;

    /**
     * @var string A HTML link representing the "next" attempt.
     */
    public $nextlink = null;

    /**
     * @var array An array of strings. One of them is just a string: the current attempt
     */
    public $attemptlinks = [];

    /**
     * Constructor mod_exescorm_attempt_bar with only the required params.
     *
     * @param array $attemptids an array of attempts the user has made
     * @param int $attempt The attempt you are currently viewing
     * @param string|moodle_url $baseurl url of the current page, the $pagevar parameter is added
     * @param string $pagevar name of page parameter that holds the attempt number
     */
    public function __construct($attemptids, $attempt, $baseurl, $pagevar = 'page') {
        $this->attemptids = $attemptids;
        $this->attempt = $attempt;
        $this->baseurl = $baseurl;
        $this->pagevar = $pagevar;
    }

    /**
     * Prepares the exescorm attempt bar for output.
     *
     * This method validates the arguments set up for the exescorm attempt bar and then
     * produces fragments of HTML to assist display later on.
     *
     * @param renderer_base $output
     * @param moodle_page $page
     * @param string $target
     * @throws coding_exception
     */
    public function prepare(renderer_base $output, moodle_page $page, $target) {
        if (empty($this->attemptids)) {
            throw new coding_exception('mod_exescorm_attempt_bar requires a attemptids value.');
        }
        if (!isset($this->attempt) || is_null($this->attempt)) {
            throw new coding_exception('mod_exescorm_attempt_bar requires a attempt value.');
        }
        if (empty($this->baseurl)) {
            throw new coding_exception('mod_exescorm_attempt_bar requires a baseurl value.');
        }

        if (count($this->attemptids) > 1) {
            $lastattempt = end($this->attemptids); // Get last attempt.
            $firstattempt = reset($this->attemptids); // Get first attempt.

            $nextattempt = 0;
            $prevattempt = null;
            $previous = 0;
            foreach ($this->attemptids as $attemptid) {
                if ($this->attempt == $attemptid) {
                    $this->attemptlinks[] = $attemptid;
                    $prevattempt = $previous;
                } else {
                    $attemptlink = html_writer::link(
                        new moodle_url($this->baseurl, [$this->pagevar => $attemptid]), $attemptid);
                    $this->attemptlinks[] = $attemptlink;
                    if (empty($nextattempt) && $prevattempt !== null) {
                        // Set the nextattempt var as we have set previous attempt earlier.
                        $nextattempt = $attemptid;
                    }
                }
                $previous = $attemptid; // Store this attempt as previous in case we need it.
            }

            if ($this->attempt != $firstattempt) {
                $this->previouslink = html_writer::link(
                    new moodle_url($this->baseurl, [$this->pagevar => $prevattempt]),
                    get_string('previous'), ['class' => 'previous']);
            }

            if ($this->attempt != $lastattempt) {
                $this->nextlink = html_writer::link(
                    new moodle_url($this->baseurl, [$this->pagevar => $nextattempt]),
                    get_string('next'), ['class' => 'next']);
            }
        }
    }
}
