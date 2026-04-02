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
 * Embedded editor installer for mod_exescorm.
 *
 * Downloads, validates, and installs the static eXeLearning editor from
 * GitHub Releases into the moodledata directory.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

/**
 * Handles downloading, validating, and installing the static eXeLearning editor.
 *
 * Installs into the moodledata directory so that the Moodle code tree is never
 * modified at runtime. Supports backup/rollback for safe replacement.
 */
class embedded_editor_installer {

    /** @var string Repository that publishes the static editor releases. */
    const GITHUB_RELEASES_REPOSITORY = 'exelearning/exelearning';

    /** @var string GitHub Atom feed for published releases. */
    const GITHUB_RELEASES_FEED_URL = 'https://github.com/exelearning/exelearning/releases.atom';

    /** @var string Filename prefix for the static editor ZIP asset. */
    const ASSET_PREFIX = 'exelearning-static-v';

    /** @var string Config key for the installed editor version. */
    const CONFIG_VERSION = 'embedded_editor_version';

    /** @var string Config key for the installation timestamp. */
    const CONFIG_INSTALLED_AT = 'embedded_editor_installed_at';

    /** @var string Config key for the concurrent-install lock. */
    const CONFIG_INSTALLING = 'embedded_editor_installing';

    /** @var int Maximum seconds to allow an install lock before considering it stale. */
    const INSTALL_LOCK_TIMEOUT = 300;

    /**
     * Install the latest static editor from GitHub Releases into moodledata.
     *
     * Orchestrates the full pipeline: discover version, download, validate,
     * extract, normalize, validate contents, safe-install, store metadata.
     *
     * @return array Associative array with 'version' and 'installed_at' keys.
     * @throws \moodle_exception On any failure during the pipeline.
     */
    public function install_latest(): array {
        $this->acquire_lock();

        try {
            $result = $this->do_install();
        } finally {
            $this->release_lock();
        }

        return $result;
    }

    /**
     * Install a specific version from GitHub Releases.
     *
     * @param string $version Version string (without leading 'v').
     * @return array Associative array with 'version' and 'installed_at' keys.
     * @throws \moodle_exception On any failure.
     */
    public function install_version(string $version): array {
        $this->acquire_lock();

        try {
            $result = $this->do_install($version);
        } finally {
            $this->release_lock();
        }

        return $result;
    }

    /**
     * Install a specific version from a ZIP file already available on disk.
     *
     * @param string $zippath Absolute path to a local ZIP file.
     * @param string $version Version string (without leading 'v').
     * @return array Associative array with 'version' and 'installed_at' keys.
     * @throws \moodle_exception On any failure.
     */
    public function install_from_local_zip(string $zippath, string $version): array {
        $this->acquire_lock();

        try {
            $result = $this->install_from_zip_path($zippath, $version, false);
        } finally {
            $this->release_lock();
        }

        return $result;
    }

    /**
     * Internal install logic, called within a lock.
     *
     * @param string|null $version Specific version to install, or null for latest.
     * @return array Associative array with 'version' and 'installed_at' keys.
     * @throws \moodle_exception On any failure.
     */
    private function do_install(?string $version = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        \core_php_time_limit::raise(self::INSTALL_LOCK_TIMEOUT);

        if ($version === null) {
            $version = $this->discover_latest_version();
        }

        $asseturl = $this->get_asset_url($version);
        $tmpfile = $this->download_to_temp($asseturl);

        return $this->install_from_zip_path($tmpfile, $version, true);
    }

    /**
     * Discover the latest release version from the GitHub Atom feed.
     *
     * @return string Version string without leading 'v'.
     * @throws \moodle_exception If GitHub is unreachable or the feed response is invalid.
     */
    public function discover_latest_version(): string {
        return $this->extract_latest_version_from_feed($this->fetch_releases_feed());
    }

    /**
     * Build the GitHub releases Atom feed URL.
     *
     * Moodle Playground now supports direct outbound PHP requests to the
     * eXeLearning GitHub feed through the configured php-wasm networking
     * fallback, so the plugin no longer needs a playground-specific URL.
     *
     * @return string
     */
    private function get_releases_feed_url(): string {
        return self::GITHUB_RELEASES_FEED_URL;
    }

    /**
     * Extract the latest release version from a GitHub releases Atom feed body.
     *
     * Uses the first <entry> because the feed is ordered newest-first. The
     * version is derived from the release tag link when available, with a
     * secondary fallback to the entry title.
     *
     * @param string $feedbody Atom feed XML as text.
     * @return string Version string without leading "v".
     * @throws \moodle_exception If no valid version can be extracted.
     */
    private function extract_latest_version_from_feed(string $feedbody): string {
        if ($feedbody === '') {
            throw new \moodle_exception('editorgithubparseerror', 'mod_exescorm');
        }

        $entrybody = $this->extract_first_feed_entry($feedbody);
        $candidate = $this->extract_version_candidate_from_entry($entrybody);
        if ($candidate === null) {
            throw new \moodle_exception('editorgithubparseerror', 'mod_exescorm');
        }

        $version = $this->normalize_version_candidate($candidate);
        if ($version === null) {
            throw new \moodle_exception('editorgithubparseerror', 'mod_exescorm', '', $candidate);
        }

        return $version;
    }

    /**
     * Download the GitHub releases Atom feed body.
     *
     * @return string
     * @throws \moodle_exception
     */
    private function fetch_releases_feed(): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setopt([
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_HTTPHEADER' => [
                'Accept: application/atom+xml, application/xml;q=0.9, text/xml;q=0.8',
                'User-Agent: Moodle mod_exescorm',
            ],
        ]);

        $response = $curl->get($this->get_releases_feed_url());
        if ($curl->get_errno()) {
            throw new \moodle_exception('editorgithubconnecterror', 'mod_exescorm', '', $curl->error);
        }

        $info = $curl->get_info();
        if (!isset($info['http_code']) || (int)$info['http_code'] !== 200) {
            $code = isset($info['http_code']) ? (int)$info['http_code'] : 0;
            throw new \moodle_exception('editorgithubapierror', 'mod_exescorm', '', $code);
        }

        return $response;
    }

    /**
     * Extract the first entry body from a GitHub Atom feed.
     *
     * @param string $feedbody Atom feed XML as text.
     * @return string
     * @throws \moodle_exception
     */
    private function extract_first_feed_entry(string $feedbody): string {
        if (!preg_match('/<entry\b[^>]*>(.*?)<\/entry>/si', $feedbody, $entrymatch)) {
            throw new \moodle_exception('editorgithubparseerror', 'mod_exescorm');
        }

        return $entrymatch[1];
    }

    /**
     * Extract a version candidate from the first available entry source.
     *
     * @param string $entrybody Entry XML fragment.
     * @return string|null
     */
    private function extract_version_candidate_from_entry(string $entrybody): ?string {
        foreach ([
            'extract_version_candidate_from_entry_link',
            'extract_version_candidate_from_entry_title',
        ] as $extractor) {
            $candidate = $this->{$extractor}($entrybody);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Extract a version candidate from the release link inside a feed entry.
     *
     * @param string $entrybody Entry XML fragment.
     * @return string|null
     */
    private function extract_version_candidate_from_entry_link(string $entrybody): ?string {
        if (!preg_match(
            '#<link[^>]+href="https://github\.com/exelearning/exelearning/releases/tag/([^"]+)"#i',
            $entrybody,
            $matches
        )) {
            return null;
        }

        return html_entity_decode(rawurldecode($matches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Extract a version candidate from the title inside a feed entry.
     *
     * @param string $entrybody Entry XML fragment.
     * @return string|null
     */
    private function extract_version_candidate_from_entry_title(string $entrybody): ?string {
        if (!preg_match('/<title\b[^>]*>(.*?)<\/title>/si', $entrybody, $matches)) {
            return null;
        }

        $title = html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $title = preg_replace('/^release\s+/i', '', $title);

        return $title !== '' ? $title : null;
    }

    /**
     * Normalize a GitHub tag/title candidate into a version string.
     *
     * @param string $candidate Raw candidate value.
     * @return string|null
     */
    private function normalize_version_candidate(string $candidate): ?string {
        $candidate = trim($candidate);
        $candidate = preg_replace('/^refs\/tags\//i', '', $candidate);
        $candidate = ltrim($candidate, 'v');

        if (!preg_match('/^\d+\.\d+(?:\.\d+)?(?:[-+._A-Za-z0-9]+)?$/', $candidate)) {
            return null;
        }

        return $candidate;
    }

    /**
     * Build the download URL for the static editor ZIP asset.
     *
     * @param string $version Version string without leading 'v'.
     * @return string Full download URL.
     */
    public function get_asset_url(string $version): string {
        $filename = self::ASSET_PREFIX . $version . '.zip';

        return 'https://github.com/exelearning/exelearning/releases/download/v' . $version . '/' . $filename;
    }

    /**
     * Download a file to a temporary location using streaming.
     *
     * @param string $url URL to download.
     * @return string Path to the downloaded temporary file.
     * @throws \moodle_exception If the download fails.
     */
    public function download_to_temp(string $url): string {
        $tempdir = make_temp_directory('mod_exescorm');
        $tmpfile = $tempdir . '/editor-download-' . random_string(12) . '.zip';

        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setopt([
            'CURLOPT_TIMEOUT' => self::INSTALL_LOCK_TIMEOUT,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 5,
        ]);

        $result = $curl->download_one($url, null, ['filepath' => $tmpfile]);

        if ($result === false || $curl->get_errno()) {
            $this->cleanup_temp_file($tmpfile);
            throw new \moodle_exception('editordownloaderror', 'mod_exescorm', '', $curl->error);
        }

        if (!is_file($tmpfile) || filesize($tmpfile) === 0) {
            $this->cleanup_temp_file($tmpfile);
            throw new \moodle_exception('editordownloaderror', 'mod_exescorm', '',
                get_string('editordownloademptyfile', 'mod_exescorm'));
        }

        return $tmpfile;
    }

    /**
     * Validate that a file is a ZIP archive by checking the PK magic bytes.
     *
     * @param string $filepath Path to the file to check.
     * @throws \moodle_exception If the file is not a valid ZIP.
     */
    public function validate_zip(string $filepath): void {
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            throw new \moodle_exception('editorinvalidzip', 'mod_exescorm');
        }
        $header = fread($handle, 4);
        fclose($handle);

        if ($header !== "PK\x03\x04") {
            throw new \moodle_exception('editorinvalidzip', 'mod_exescorm');
        }
    }


    /**
     * Install the editor from a ZIP file path.
     *
     * @param string $zippath Path to the ZIP file.
     * @param string $version Version string without leading 'v'.
     * @param bool $cleanupzip Whether to remove the ZIP file afterwards.
     * @return array Associative array with 'version' and 'installed_at' keys.
     * @throws \moodle_exception If validation or installation fails.
     */
    private function install_from_zip_path(string $zippath, string $version, bool $cleanupzip): array {
        try {
            $this->validate_zip($zippath);
        } catch (\moodle_exception $e) {
            if ($cleanupzip) {
                $this->cleanup_temp_file($zippath);
            }
            throw $e;
        }

        $tmpdir = null;
        try {
            $tmpdir = $this->extract_to_temp($zippath);
            if ($cleanupzip) {
                $this->cleanup_temp_file($zippath);
            }

            $sourcedir = $this->normalize_extraction($tmpdir);
            $this->validate_editor_contents($sourcedir);
            $this->safe_install($sourcedir);
        } catch (\moodle_exception $e) {
            if ($cleanupzip) {
                $this->cleanup_temp_file($zippath);
            }
            if ($tmpdir !== null) {
                $this->cleanup_temp_dir($tmpdir);
            }
            throw $e;
        }

        if ($tmpdir !== null) {
            $this->cleanup_temp_dir($tmpdir);
        }
        $this->store_metadata($version);

        return [
            'version' => $version,
            'installed_at' => self::get_installed_at(),
        ];
    }

    /**
     * Extract a ZIP file to a temporary directory.
     *
     * @param string $zippath Path to the ZIP file.
     * @return string Path to the temporary extraction directory.
     * @throws \moodle_exception If extraction fails.
     */
    public function extract_to_temp(string $zippath): string {
        if (!class_exists('ZipArchive')) {
            throw new \moodle_exception('editorzipextensionmissing', 'mod_exescorm');
        }

        $tmpdir = make_temp_directory('mod_exescorm/extract-' . random_string(12));

        $zip = new \ZipArchive();
        $result = $zip->open($zippath);
        if ($result !== true) {
            throw new \moodle_exception('editorextractfailed', 'mod_exescorm', '', $result);
        }

        if (!$zip->extractTo($tmpdir)) {
            $zip->close();
            $this->cleanup_temp_dir($tmpdir);
            throw new \moodle_exception('editorextractfailed', 'mod_exescorm', '',
                get_string('editorextractwriteerror', 'mod_exescorm'));
        }

        $zip->close();
        return $tmpdir;
    }

    /**
     * Normalize the extracted directory layout to find the actual editor root.
     *
     * Handles three common patterns:
     * 1. index.html at extraction root.
     * 2. Single subdirectory containing index.html.
     * 3. Double-nested single subdirectory containing index.html.
     *
     * @param string $tmpdir Path to the extraction directory.
     * @return string Path to the directory containing index.html.
     * @throws \moodle_exception If index.html cannot be found.
     */
    public function normalize_extraction(string $tmpdir): string {
        $tmpdir = rtrim($tmpdir, '/');

        // Pattern 1: files directly at root.
        if (is_file($tmpdir . '/index.html')) {
            return $tmpdir;
        }

        // Pattern 2: single top-level directory.
        $entries = array_diff(scandir($tmpdir), ['.', '..']);
        if (count($entries) === 1) {
            $singleentry = $tmpdir . '/' . reset($entries);
            if (is_dir($singleentry) && is_file($singleentry . '/index.html')) {
                return $singleentry;
            }
        }

        // Pattern 3: double-nested wrapper.
        foreach ($entries as $entry) {
            $entrypath = $tmpdir . '/' . $entry;
            if (is_dir($entrypath)) {
                $subentries = array_diff(scandir($entrypath), ['.', '..']);
                if (count($subentries) === 1) {
                    $subentry = $entrypath . '/' . reset($subentries);
                    if (is_dir($subentry) && is_file($subentry . '/index.html')) {
                        return $subentry;
                    }
                }
            }
        }

        throw new \moodle_exception('editorinvalidlayout', 'mod_exescorm');
    }

    /**
     * Validate that a directory contains the expected editor files.
     *
     * @param string $sourcedir Path to the editor directory.
     * @throws \moodle_exception If validation fails.
     */
    public function validate_editor_contents(string $sourcedir): void {
        if (!embedded_editor_source_resolver::validate_editor_dir($sourcedir)) {
            throw new \moodle_exception('editorinvalidlayout', 'mod_exescorm');
        }
    }

    /**
     * Safely install the editor from a source directory to moodledata.
     *
     * Uses atomic rename with backup/rollback for reliability.
     *
     * @param string $sourcedir Path to the validated source directory.
     * @throws \moodle_exception If installation fails.
     */
    public function safe_install(string $sourcedir): void {
        $targetdir = embedded_editor_source_resolver::get_moodledata_dir();
        $parentdir = dirname($targetdir);

        // Ensure parent directory exists and is writable.
        if (!is_dir($parentdir)) {
            if (!make_writable_directory($parentdir)) {
                throw new \moodle_exception('editorinstallfailed', 'mod_exescorm', '',
                    get_string('editormkdirerror', 'mod_exescorm', $parentdir));
            }
        }

        $backupdir = null;
        $hadexisting = is_dir($targetdir);

        if ($hadexisting) {
            $backupdir = $parentdir . '/embedded_editor-backup-' . time();
            if (!@rename($targetdir, $backupdir)) {
                throw new \moodle_exception('editorinstallfailed', 'mod_exescorm', '',
                    get_string('editorbackuperror', 'mod_exescorm'));
            }
        }

        // Try atomic rename first (fast, same filesystem since both are under dataroot).
        $installed = @rename(rtrim($sourcedir, '/'), $targetdir);

        if (!$installed) {
            // Fallback: recursive copy.
            if (!is_dir($targetdir)) {
                @mkdir($targetdir, 0777, true);
            }
            $installed = $this->recursive_copy($sourcedir, $targetdir);
        }

        if (!$installed) {
            // Restore backup on failure.
            if ($hadexisting && $backupdir !== null && is_dir($backupdir)) {
                if (is_dir($targetdir)) {
                    remove_dir($targetdir);
                }
                @rename($backupdir, $targetdir);
            }
            throw new \moodle_exception('editorinstallfailed', 'mod_exescorm', '',
                get_string('editorcopyfailed', 'mod_exescorm'));
        }

        // Clean up backup on success.
        if ($hadexisting && $backupdir !== null && is_dir($backupdir)) {
            remove_dir($backupdir);
        }
    }

    /**
     * Remove the admin-installed editor from moodledata and clear metadata.
     */
    public function uninstall(): void {
        $dir = embedded_editor_source_resolver::get_moodledata_dir();
        if (is_dir($dir)) {
            remove_dir($dir);
        }
        $this->clear_metadata();
    }

    /**
     * Store installation metadata in plugin config.
     *
     * @param string $version The installed version string.
     */
    public function store_metadata(string $version): void {
        set_config(self::CONFIG_VERSION, $version, 'exescorm');
        set_config(self::CONFIG_INSTALLED_AT, date('Y-m-d H:i:s'), 'exescorm');
    }

    /**
     * Clear installation metadata from plugin config.
     */
    public function clear_metadata(): void {
        unset_config(self::CONFIG_VERSION, 'exescorm');
        unset_config(self::CONFIG_INSTALLED_AT, 'exescorm');
    }

    /**
     * Get the currently stored installed version.
     *
     * @return string|null Version string or null.
     */
    public static function get_installed_version(): ?string {
        return embedded_editor_source_resolver::get_moodledata_version();
    }

    /**
     * Get the currently stored installation timestamp.
     *
     * @return string|null Datetime string or null.
     */
    public static function get_installed_at(): ?string {
        return embedded_editor_source_resolver::get_moodledata_installed_at();
    }

    /**
     * Acquire a lock to prevent concurrent installations.
     *
     * @throws \moodle_exception If another installation is already in progress.
     */
    private function acquire_lock(): void {
        $locktime = get_config('exescorm', self::CONFIG_INSTALLING);
        if ($locktime !== false && (time() - (int)$locktime) < self::INSTALL_LOCK_TIMEOUT) {
            throw new \moodle_exception('editorinstallconcurrent', 'mod_exescorm');
        }
        set_config(self::CONFIG_INSTALLING, time(), 'exescorm');
    }

    /**
     * Release the installation lock.
     */
    private function release_lock(): void {
        unset_config(self::CONFIG_INSTALLING, 'exescorm');
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     * @return bool True on success.
     */
    private function recursive_copy(string $src, string $dst): bool {
        $src = rtrim($src, '/');
        $dst = rtrim($dst, '/');

        if (!is_dir($dst)) {
            if (!@mkdir($dst, 0777, true)) {
                return false;
            }
        }

        $entries = scandir($src);
        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $srcpath = $src . '/' . $entry;
            $dstpath = $dst . '/' . $entry;

            if (is_dir($srcpath)) {
                if (!$this->recursive_copy($srcpath, $dstpath)) {
                    return false;
                }
            } else {
                if (!@copy($srcpath, $dstpath)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Clean up a temporary file.
     *
     * @param string $filepath Path to the file.
     */
    private function cleanup_temp_file(string $filepath): void {
        if (is_file($filepath)) {
            @unlink($filepath);
        }
    }

    /**
     * Clean up a temporary directory.
     *
     * @param string $dir Path to the directory.
     */
    private function cleanup_temp_dir(string $dir): void {
        if (is_dir($dir)) {
            remove_dir($dir);
        }
    }
}
