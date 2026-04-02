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
 * External functions for managing the embedded editor in mod_exescorm.
 *
 * @package    mod_exescorm
 * @category   external
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_exescorm\local\embedded_editor_installer;
use mod_exescorm\local\embedded_editor_source_resolver;

/**
 * External API for managing the embedded eXeLearning editor.
 *
 * Provides AJAX-accessible functions to install, update, repair, and uninstall
 * the admin-installed embedded editor, and to query its current status.
 */
class manage_embedded_editor extends external_api {

    // -------------------------------------------------------------------------
    // execute_action
    // -------------------------------------------------------------------------

    /**
     * Parameter definition for execute_action.
     *
     * @return external_function_parameters
     */
    public static function execute_action_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(
                PARAM_ALPHA,
                'Action to perform: install, update, repair, or uninstall'
            ),
        ]);
    }

    /**
     * Execute an install/update/repair/uninstall action on the embedded editor.
     *
     * Requires both moodle/site:config AND mod/exescorm:manageembeddededitor
     * capabilities in the system context.
     *
     * @param string $action One of: install, update, repair, uninstall.
     * @return array Result array with success, action, message, version, installed_at.
     * @throws \invalid_parameter_exception If action is not valid.
     * @throws \required_capability_exception If the user lacks required capabilities.
     * @throws \moodle_exception On installer failure.
     */
    public static function execute_action(string $action): array {
        $params = self::validate_parameters(self::execute_action_parameters(), ['action' => $action]);
        $action = $params['action'];

        $validactions = ['install', 'update', 'repair', 'uninstall'];
        if (!in_array($action, $validactions, true)) {
            throw new \invalid_parameter_exception(
                get_string('invalidaction', 'mod_exescorm', $action)
            );
        }

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);
        require_capability('mod/exescorm:manageembeddededitor', $context);

        $installer = new embedded_editor_installer();

        $version = '';
        $installedat = '';

        switch ($action) {
            case 'install':
            case 'update':
                $result = $installer->install_latest();
                $version = $result['version'] ?? '';
                $installedat = $result['installed_at'] ?? '';
                break;

            case 'repair':
                $installer->uninstall();
                $result = $installer->install_latest();
                $version = $result['version'] ?? '';
                $installedat = $result['installed_at'] ?? '';
                break;

            case 'uninstall':
                $installer->uninstall();
                break;
        }

        return [
            'success'      => true,
            'action'       => $action,
            'message'      => self::get_action_success_string($action),
            'version'      => $version,
            'installed_at' => $installedat,
        ];
    }

    /**
     * Map action name to its success lang string key.
     *
     * @param string $action The action that was performed.
     * @return string The localised success message.
     */
    public static function get_action_success_string(string $action): string {
        $map = [
            'install'   => 'editorinstalledsuccess',
            'update'    => 'editorupdatedsuccess',
            'repair'    => 'editorrepairsuccess',
            'uninstall' => 'editoruninstalledsuccess',
        ];
        return get_string($map[$action], 'mod_exescorm');
    }

    /**
     * Return value definition for execute_action.
     *
     * @return external_single_structure
     */
    public static function execute_action_returns(): external_single_structure {
        return new external_single_structure([
            'success'      => new external_value(PARAM_BOOL, 'Whether the action succeeded'),
            'action'       => new external_value(PARAM_ALPHA, 'The action that was performed'),
            'message'      => new external_value(PARAM_TEXT, 'Human-readable result message'),
            'version'      => new external_value(PARAM_TEXT, 'Installed version, empty when uninstalling', VALUE_OPTIONAL, ''),
            'installed_at' => new external_value(PARAM_TEXT, 'Installation timestamp, empty when uninstalling', VALUE_OPTIONAL, ''),
        ]);
    }

    // -------------------------------------------------------------------------
    // get_status
    // -------------------------------------------------------------------------

    /**
     * Parameter definition for get_status.
     *
     * @return external_function_parameters
     */
    public static function get_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'checklatest' => new external_value(
                PARAM_BOOL,
                'When true, query GitHub for the latest available version',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Return the current status of the embedded editor installation.
     *
     * When checklatest is true, queries GitHub to discover the latest release
     * version from the public Atom feed. Otherwise returns only locally
     * cached/config state.
     *
     * Checks the CONFIG_INSTALLING lock and reports installing=true when the
     * lock is active, and install_stale=true when the lock age exceeds
     * INSTALL_LOCK_TIMEOUT seconds.
     *
     * @param bool $checklatest When true, call discover_latest_version() via the GitHub Atom feed.
     * @return array Status array.
     */
    public static function get_status(bool $checklatest): array {
        $params = self::validate_parameters(self::get_status_parameters(), ['checklatest' => $checklatest]);
        $checklatest = $params['checklatest'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);
        require_capability('mod/exescorm:manageembeddededitor', $context);

        // Resolve source state.
        $localstatus = embedded_editor_source_resolver::get_status();

        // Install lock state.
        $locktime = get_config('exescorm', embedded_editor_installer::CONFIG_INSTALLING);
        $installing = false;
        $installstale = false;
        if ($locktime !== false && $locktime !== '') {
            $elapsed = time() - (int)$locktime;
            if ($elapsed < embedded_editor_installer::INSTALL_LOCK_TIMEOUT) {
                $installing = true;
            } else {
                $installstale = true;
            }
        }

        // Latest version from GitHub (optional).
        $latestversion = '';
        $latesterror = '';
        if ($checklatest) {
            try {
                $installer = new embedded_editor_installer();
                $latestversion = $installer->discover_latest_version();
            } catch (\moodle_exception $e) {
                $latesterror = $e->getMessage();
            }
        }

        // Derive update_available.
        $moodledataversion = $localstatus->moodledata_version ?? '';
        $updateavailable = (
            $latestversion !== ''
            && $moodledataversion !== ''
            && version_compare($latestversion, $moodledataversion, '>')
        );

        // Capability flags.
        $canconfigure = has_capability('moodle/site:config', $context)
            && has_capability('mod/exescorm:manageembeddededitor', $context);

        $moodledataavailable = $localstatus->moodledata_available ?? false;

        return [
            'active_source'        => $localstatus->active_source,
            'moodledata_available' => (bool)$moodledataavailable,
            'moodledata_version'   => (string)($moodledataversion ?? ''),
            'moodledata_installed_at' => (string)($localstatus->moodledata_installed_at ?? ''),
            'bundled_available'    => (bool)($localstatus->bundled_available ?? false),
            'latest_version'       => $latestversion,
            'latest_error'         => $latesterror,
            'update_available'     => $updateavailable,
            'installing'           => $installing,
            'install_stale'        => $installstale,
            'can_install'          => $canconfigure && !$moodledataavailable,
            'can_update'           => $canconfigure && $moodledataavailable && $updateavailable,
            'can_repair'           => $canconfigure && $moodledataavailable,
            'can_uninstall'        => $canconfigure && $moodledataavailable,
        ];
    }

    /**
     * Return value definition for get_status.
     *
     * @return external_single_structure
     */
    public static function get_status_returns(): external_single_structure {
        return new external_single_structure([
            'active_source'           => new external_value(PARAM_TEXT, 'Active source: moodledata, bundled, or none'),
            'moodledata_available'    => new external_value(PARAM_BOOL, 'Whether the admin-installed editor is present and valid'),
            'moodledata_version'      => new external_value(PARAM_TEXT, 'Installed version in moodledata, empty if unknown'),
            'moodledata_installed_at' => new external_value(PARAM_TEXT, 'Installation timestamp for moodledata editor, empty if unknown'),
            'bundled_available'       => new external_value(PARAM_BOOL, 'Whether the bundled editor is present and valid'),
            'latest_version'          => new external_value(PARAM_TEXT, 'Latest version from GitHub, empty if not checked or on error'),
            'latest_error'            => new external_value(PARAM_TEXT, 'Error message from GitHub check, empty on success'),
            'update_available'        => new external_value(PARAM_BOOL, 'Whether a newer version is available on GitHub'),
            'installing'              => new external_value(PARAM_BOOL, 'Whether an installation is currently in progress'),
            'install_stale'           => new external_value(PARAM_BOOL, 'Whether the install lock is stale (exceeded timeout)'),
            'can_install'             => new external_value(PARAM_BOOL, 'Whether the current user can perform an install'),
            'can_update'              => new external_value(PARAM_BOOL, 'Whether the current user can perform an update'),
            'can_repair'              => new external_value(PARAM_BOOL, 'Whether the current user can perform a repair'),
            'can_uninstall'           => new external_value(PARAM_BOOL, 'Whether the current user can perform an uninstall'),
        ]);
    }
}
