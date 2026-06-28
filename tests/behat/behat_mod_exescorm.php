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
 * Step definitions for mod_exescorm Behat scenarios.
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2026 ATE (Área de Tecnología Educativa)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * mod_exescorm-specific Behat steps.
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2026 ATE (Área de Tecnología Educativa)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_exescorm extends behat_base {

    /**
     * Reads the URL the package iframe (#exescorm_object) has navigated to.
     *
     * The player renders an iframe whose src is loadSCO.php; that page redirects the
     * iframe document to the actual eXeLearning content URL, which is where the
     * ?exe-teacher=1 parameter (upstream exelearning#1772) lives. We read it from the
     * iframe's content window location so the assertion reflects what the loaded
     * package sees on window.location.search.
     *
     * @return string The package iframe content-window href, or '' if unavailable.
     */
    protected function get_package_iframe_url(): string {
        $js = <<<JS
(function() {
    var f = document.getElementById('exescorm_object');
    if (!f) { return ''; }
    try {
        return String(f.contentWindow.location.href);
    } catch (e) {
        return String(f.getAttribute('src') || '');
    }
})()
JS;
        return (string) $this->evaluate_script($js);
    }

    /**
     * Checks the package iframe has navigated to a URL containing the given text.
     *
     * @Then the eXeLearning content iframe url should contain :text
     * @param string $text The substring expected in the iframe content URL.
     * @throws ExpectationException When the substring is not present.
     */
    public function the_exelearning_content_iframe_url_should_contain(string $text): void {
        $this->spin(
            function() use ($text) {
                $url = $this->get_package_iframe_url();
                if (strpos($url, $text) !== false) {
                    return true;
                }
                throw new ExpectationException(
                    "The package iframe url '{$url}' does not contain '{$text}'.",
                    $this->getSession()
                );
            },
            false,
            10
        );
    }

    /**
     * Checks the package iframe has navigated to a URL NOT containing the given text.
     *
     * @Then the eXeLearning content iframe url should not contain :text
     * @param string $text The substring expected to be absent from the iframe content URL.
     * @throws ExpectationException When the substring is present.
     */
    public function the_exelearning_content_iframe_url_should_not_contain(string $text): void {
        $url = $this->get_package_iframe_url();
        if (strpos($url, $text) !== false) {
            throw new ExpectationException(
                "The package iframe url '{$url}' unexpectedly contains '{$text}'.",
                $this->getSession()
            );
        }
    }
}
