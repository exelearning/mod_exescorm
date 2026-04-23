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
 * Style package registry and ZIP validator for mod_exescorm.
 *
 * Administrators upload eXeLearning style packages as .zip files. This
 * service validates them, extracts them into moodledata, records metadata
 * in `config_plugin(exeweb)`, and builds the registry payload that the
 * embedded editor consumes via `window.eXeLearning.config.themeRegistryOverride`.
 *
 * Uploaded styles live at:
 *   {dataroot}/mod_exescorm/styles/{slug}/
 * which is a *sibling* of the admin-installed editor directory
 * ({dataroot}/mod_exescorm/embedded_editor/), so reinstalling the editor
 * never destroys admin-managed styles.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

/**
 * Class styles_service.
 */
class styles_service {

    /** @var string Subdirectory under $CFG->dataroot holding uploaded styles. */
    const MOODLEDATA_SUBDIR = 'mod_exescorm/styles';

    /** @var string Plugin config key storing the serialized registry. */
    const CONFIG_REGISTRY = 'styles_registry';

    /** @var int Default max allowed ZIP size (20 MB). */
    const DEFAULT_MAX_ZIP_SIZE = 20971520;

    /** @var string[] Allow-list of extensions inside an uploaded style ZIP. */
    const ALLOWED_EXTENSIONS = [
        'css', 'js', 'map', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico',
        'xml', 'json', 'md', 'txt', 'html', 'htm',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
    ];

    // ---------------------------------------------------------------------
    // Storage helpers
    // ---------------------------------------------------------------------

    /**
     * Absolute path to the directory that stores uploaded styles.
     *
     * @return string
     */
    public static function get_storage_dir(): string {
        global $CFG;
        return $CFG->dataroot . '/' . self::MOODLEDATA_SUBDIR;
    }

    /**
     * Absolute path for a specific uploaded style.
     *
     * @param string $slug
     * @return string
     */
    public static function get_style_dir(string $slug): string {
        return self::get_storage_dir() . '/' . self::normalize_slug($slug);
    }

    /**
     * Build the public URL prefix (served via editor/styles.php) for a slug.
     *
     * @param string $slug
     * @return string
     */
    public static function get_style_url(string $slug): string {
        global $CFG;
        return $CFG->wwwroot . '/mod/exescorm/editor/styles.php/' . rawurlencode(self::normalize_slug($slug));
    }

    /**
     * Maximum allowed upload size in bytes.
     *
     * @return int
     */
    public static function get_max_zip_size(): int {
        $configured = (int) get_config('exeweb', 'styles_max_zip_size');
        return $configured > 0 ? $configured : self::DEFAULT_MAX_ZIP_SIZE;
    }

    // ---------------------------------------------------------------------
    // Registry persistence
    // ---------------------------------------------------------------------

    /**
     * Load the persisted registry.
     *
     * @return array{uploaded: array<string,array>, disabled_builtins: string[]}
     */
    public static function get_registry(): array {
        $raw = get_config('exeweb', self::CONFIG_REGISTRY);
        if (!is_string($raw) || $raw === '' || $raw === 'false') {
            $data = [];
        } else {
            $decoded = json_decode($raw, true);
            $data = is_array($decoded) ? $decoded : [];
        }
        return [
            'uploaded' => isset($data['uploaded']) && is_array($data['uploaded']) ? $data['uploaded'] : [],
            'disabled_builtins' => isset($data['disabled_builtins']) && is_array($data['disabled_builtins'])
                ? array_values(array_map('strval', $data['disabled_builtins']))
                : [],
        ];
    }

    /**
     * Persist the registry.
     *
     * @param array $registry
     */
    public static function save_registry(array $registry): void {
        set_config(self::CONFIG_REGISTRY, json_encode($registry), 'exeweb');
    }

    // ---------------------------------------------------------------------
    // Public listing
    // ---------------------------------------------------------------------

    /**
     * List built-in themes discovered from the bundled editor's manifest.
     *
     * Returns an empty array if no editor is installed yet.
     *
     * @return array<int, array<string,string>>
     */
    public static function list_builtin_themes(): array {
        $active = embedded_editor_source_resolver::get_active_dir();
        if ($active === null) {
            return [];
        }
        $bundlepath = rtrim($active, '/') . '/data/bundle.json';
        if (!is_file($bundlepath) || !is_readable($bundlepath)) {
            return [];
        }
        $json = @file_get_contents($bundlepath);
        if ($json === false || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['themes'])) {
            return [];
        }
        $themes = $data['themes'];
        // bundle.json serializes `themes: { themes: [..] }`; accept flat too.
        if (is_array($themes) && isset($themes['themes']) && is_array($themes['themes'])) {
            $themes = $themes['themes'];
        }
        if (!is_array($themes)) {
            return [];
        }
        $out = [];
        foreach ($themes as $theme) {
            if (!is_array($theme) || empty($theme['name'])) {
                continue;
            }
            $out[] = [
                'id' => (string) $theme['name'],
                'name' => (string) $theme['name'],
                'title' => (string) ($theme['title'] ?? $theme['name']),
                'version' => (string) ($theme['version'] ?? ''),
                'description' => (string) ($theme['description'] ?? ''),
                'author' => (string) ($theme['author'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * List uploaded styles enriched with computed URL info.
     *
     * @return array<int, array<string,mixed>>
     */
    public static function list_uploaded_styles(): array {
        $registry = self::get_registry();
        $out = [];
        foreach ($registry['uploaded'] as $slug => $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $meta['id'] = (string) $slug;
            $meta['name'] = (string) $slug;
            $meta['url'] = self::get_style_url($slug);
            $meta['path'] = self::get_style_dir($slug);
            $out[] = $meta;
        }
        return $out;
    }

    /**
     * Build the payload consumed by the editor's themeRegistryOverride hook.
     *
     * @return array
     */
    public static function build_theme_registry_override(): array {
        $registry = self::get_registry();
        $uploaded = [];
        foreach ($registry['uploaded'] as $slug => $meta) {
            if (!is_array($meta) || empty($meta['enabled'])) {
                continue;
            }
            $cssfiles = isset($meta['css_files']) && is_array($meta['css_files'])
                ? array_values(array_map('strval', $meta['css_files']))
                : ['style.css'];
            $uploaded[] = [
                'id' => (string) $slug,
                'name' => (string) $slug,
                'dirName' => (string) $slug,
                'title' => (string) ($meta['title'] ?? $slug),
                'description' => (string) ($meta['description'] ?? ''),
                'version' => (string) ($meta['version'] ?? ''),
                'author' => (string) ($meta['author'] ?? ''),
                'license' => (string) ($meta['license'] ?? ''),
                'type' => 'uploaded',
                'url' => self::get_style_url($slug),
                'cssFiles' => $cssfiles,
                'downloadable' => '0',
                'valid' => true,
            ];
        }
        return [
            'disabledBuiltins' => $registry['disabled_builtins'],
            'uploaded' => $uploaded,
            'blockImportInstall' => self::is_import_blocked(),
            'fallbackTheme' => 'base',
        ];
    }

    /**
     * Whether the admin has disabled user-imported styles (tab hidden,
     * project-bundled styles silently ignored). Mirrors the eXeLearning
     * `ONLINE_THEMES_INSTALL=false` policy.
     *
     * Defaults to true on first install so the editor remains locked down
     * until an admin explicitly opts in.
     *
     * @return bool
     */
    public static function is_import_blocked(): bool {
        $value = get_config('exeweb', 'stylesblockimport');
        if ($value === false || $value === '' || $value === null) {
            return true;
        }
        return (bool) $value;
    }

    // ---------------------------------------------------------------------
    // State changes
    // ---------------------------------------------------------------------

    /**
     * Toggle the enabled flag on an uploaded style.
     *
     * @param string $slug
     * @param bool $enabled
     * @return bool True on success; false if no such slug.
     */
    public static function set_uploaded_enabled(string $slug, bool $enabled): bool {
        $slug = self::normalize_slug($slug);
        $registry = self::get_registry();
        if (!isset($registry['uploaded'][$slug])) {
            return false;
        }
        $registry['uploaded'][$slug]['enabled'] = $enabled;
        self::save_registry($registry);
        return true;
    }

    /**
     * Toggle a built-in style (true = visible, false = hidden).
     *
     * @param string $id
     * @param bool $enabled
     */
    public static function set_builtin_enabled(string $id, bool $enabled): void {
        $id = self::normalize_slug($id);
        $registry = self::get_registry();
        $disabled = $registry['disabled_builtins'];
        if ($enabled) {
            $disabled = array_values(array_filter($disabled, static fn($d) => $d !== $id));
        } else if (!in_array($id, $disabled, true)) {
            $disabled[] = $id;
        }
        $registry['disabled_builtins'] = $disabled;
        self::save_registry($registry);
    }

    /**
     * Delete an uploaded style (registry entry + extracted files).
     *
     * @param string $slug
     * @return bool True on success; false if no such slug.
     */
    public static function delete_uploaded(string $slug): bool {
        $slug = self::normalize_slug($slug);
        $registry = self::get_registry();
        if (!isset($registry['uploaded'][$slug])) {
            return false;
        }
        $dir = self::get_style_dir($slug);
        if (is_dir($dir)) {
            self::recursive_delete($dir);
        }
        unset($registry['uploaded'][$slug]);
        self::save_registry($registry);
        return true;
    }

    // ---------------------------------------------------------------------
    // ZIP install pipeline
    // ---------------------------------------------------------------------

    /**
     * Install a style from a ZIP file on disk.
     *
     * @param string $zippath Absolute path to the uploaded ZIP.
     * @param string $origname Original filename (fallback for slug).
     * @return array Registry entry.
     * @throws \moodle_exception When validation or extraction fails.
     */
    public static function install_from_zip(string $zippath, string $origname = ''): array {
        $validation = self::validate_zip($zippath);
        $config = $validation['config'];
        $prefix = $validation['prefix'];

        $requestedslug = !empty($config['name']) ? $config['name'] : pathinfo($origname, PATHINFO_FILENAME);
        $slug = self::allocate_unique_slug($requestedslug);

        $dest = self::get_style_dir($slug);
        if (!check_dir_exists($dest, true, true)) {
            throw new \moodle_exception('stylesinstallfailed', 'mod_exescorm', '', 'mkdir');
        }

        try {
            self::extract_zip_safely($zippath, $dest, $prefix);
        } catch (\Throwable $e) {
            self::recursive_delete($dest);
            throw $e;
        }

        $cssfiles = self::find_css_files($dest);
        if (empty($cssfiles)) {
            self::recursive_delete($dest);
            throw new \moodle_exception('stylesnocss', 'mod_exescorm');
        }

        $entry = [
            'title' => (string) ($config['title'] ?? $slug),
            'version' => (string) ($config['version'] ?? ''),
            'author' => (string) ($config['author'] ?? ''),
            'license' => (string) ($config['license'] ?? ''),
            'description' => (string) ($config['description'] ?? ''),
            'css_files' => $cssfiles,
            'enabled' => true,
            'installed_at' => gmdate('c'),
            'checksum' => self::hash_zip($zippath),
            'size' => (int) @filesize($zippath),
        ];

        $registry = self::get_registry();
        $registry['uploaded'][$slug] = $entry;
        self::save_registry($registry);

        $entry['id'] = $slug;
        $entry['name'] = $slug;
        return $entry;
    }

    /**
     * Validate an uploaded ZIP.
     *
     * @param string $zippath
     * @return array{config: array<string,string>, prefix: string}
     * @throws \moodle_exception
     */
    public static function validate_zip(string $zippath): array {
        if (!is_file($zippath) || !is_readable($zippath)) {
            throw new \moodle_exception('stylesupload_missing', 'mod_exescorm');
        }
        $size = filesize($zippath);
        if ($size === false || $size <= 0) {
            throw new \moodle_exception('stylesupload_empty', 'mod_exescorm');
        }
        if ($size > self::get_max_zip_size()) {
            throw new \moodle_exception('stylesupload_toolarge', 'mod_exescorm', '',
                display_size(self::get_max_zip_size()));
        }
        if (!class_exists('\ZipArchive')) {
            throw new \moodle_exception('stylesupload_nozip', 'mod_exescorm');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zippath, \ZipArchive::CHECKCONS) !== true) {
            throw new \moodle_exception('stylesupload_badzip', 'mod_exescorm');
        }

        $configpath = null;
        $prefix = null;
        $entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                $zip->close();
                throw new \moodle_exception('stylesupload_badentry', 'mod_exescorm');
            }
            $name = (string) $stat['name'];
            if (self::is_unsafe_zip_entry($name)) {
                $zip->close();
                throw new \moodle_exception('stylesupload_unsafe', 'mod_exescorm', '', $name);
            }
            $entries[] = $name;
            if (basename($name) === 'config.xml') {
                if ($configpath !== null) {
                    $zip->close();
                    throw new \moodle_exception('stylesupload_multiconfig', 'mod_exescorm');
                }
                $configpath = $name;
                $dirname = trim(str_replace('\\', '/', dirname($name)), '/');
                $prefix = ($dirname === '' || $dirname === '.') ? '' : $dirname . '/';
            }
        }

        if ($configpath === null) {
            $zip->close();
            throw new \moodle_exception('stylesupload_noconfig', 'mod_exescorm');
        }

        foreach ($entries as $entry) {
            if ($prefix === '') {
                // When config.xml is at the root, subdirectories under root are allowed.
                if (strpos($entry, '/') !== false) {
                    // But the extension check still applies to files.
                }
            } else if (strpos($entry, $prefix) !== 0) {
                $zip->close();
                throw new \moodle_exception('stylesupload_mixedroots', 'mod_exescorm');
            }
            if (!self::is_allowed_filename($entry)) {
                $zip->close();
                throw new \moodle_exception('stylesupload_badext', 'mod_exescorm', '', $entry);
            }
        }

        $configxml = $zip->getFromName($configpath);
        $zip->close();
        if ($configxml === false) {
            throw new \moodle_exception('stylesupload_configread', 'mod_exescorm');
        }

        return [
            'config' => self::parse_config_xml($configxml),
            'prefix' => (string) $prefix,
        ];
    }

    /**
     * Parse config.xml into an associative array. Throws on invalid XML or
     * missing mandatory <name>.
     *
     * @param string $source
     * @return array<string,string>
     * @throws \moodle_exception
     */
    public static function parse_config_xml(string $source): array {
        $preverrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($source, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors($preverrors);
        if ($xml === false) {
            throw new \moodle_exception('stylesupload_badxml', 'mod_exescorm');
        }
        $name = isset($xml->name) ? trim((string) $xml->name) : '';
        if ($name === '') {
            throw new \moodle_exception('stylesupload_noname', 'mod_exescorm');
        }
        return [
            'name' => self::normalize_slug($name),
            'title' => isset($xml->title) ? (string) $xml->title : $name,
            'version' => isset($xml->version) ? (string) $xml->version : '',
            'author' => isset($xml->author) ? (string) $xml->author : '',
            'license' => isset($xml->license) ? (string) $xml->license : '',
            'description' => isset($xml->description) ? (string) $xml->description : '',
        ];
    }

    /**
     * Extract a ZIP's contents into $dest, stripping $prefix if non-empty,
     * with per-entry safety checks.
     *
     * @param string $zippath
     * @param string $dest
     * @param string $prefix
     * @throws \moodle_exception
     */
    private static function extract_zip_safely(string $zippath, string $dest, string $prefix): void {
        $zip = new \ZipArchive();
        if ($zip->open($zippath, \ZipArchive::CHECKCONS) !== true) {
            throw new \moodle_exception('stylesupload_badzip', 'mod_exescorm');
        }
        $destreal = rtrim(str_replace('\\', '/', $dest), '/');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }
            $name = (string) $stat['name'];
            if (self::is_unsafe_zip_entry($name)) {
                $zip->close();
                throw new \moodle_exception('stylesupload_unsafe', 'mod_exescorm', '', $name);
            }
            $relative = $name;
            if ($prefix !== '') {
                if (strpos($name, $prefix) !== 0) {
                    continue;
                }
                $relative = substr($name, strlen($prefix));
                if ($relative === '') {
                    continue;
                }
            }
            $target = $destreal . '/' . ltrim($relative, '/');
            $target = str_replace('\\', '/', $target);
            if (strpos($target, $destreal . '/') !== 0 && $target !== $destreal) {
                $zip->close();
                throw new \moodle_exception('stylesupload_traversal', 'mod_exescorm');
            }
            if (substr($name, -1) === '/') {
                check_dir_exists($target, true, true);
                continue;
            }
            check_dir_exists(dirname($target), true, true);
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $zip->close();
                throw new \moodle_exception('stylesupload_readfailed', 'mod_exescorm');
            }
            if (file_put_contents($target, $contents) === false) {
                $zip->close();
                throw new \moodle_exception('stylesupload_writefailed', 'mod_exescorm');
            }
        }
        $zip->close();
    }

    // ---------------------------------------------------------------------
    // Internal helpers (also exposed for tests)
    // ---------------------------------------------------------------------

    /**
     * Entries that must never be extracted (absolute paths, traversal, streams, empty).
     *
     * @param string $name
     * @return bool
     */
    public static function is_unsafe_zip_entry(string $name): bool {
        if ($name === '') {
            return true;
        }
        if (strpos($name, '\\') !== false) {
            return true;
        }
        if (strpos($name, '/') === 0) {
            return true;
        }
        if (preg_match('#^[a-zA-Z]+://#', $name)) {
            return true;
        }
        if (preg_match('#(^|/)\.\.(/|$)#', $name)) {
            return true;
        }
        return false;
    }

    /**
     * Allow-list check for filenames inside the archive.
     *
     * @param string $name
     * @return bool
     */
    public static function is_allowed_filename(string $name): bool {
        if ($name === '' || substr($name, -1) === '/') {
            return true;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '') {
            return false;
        }
        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Normalize a user-supplied id into a safe slug.
     *
     * @param string $slug
     * @return string
     */
    public static function normalize_slug(string $slug): string {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug === '' ? 'style' : $slug;
    }

    /**
     * Allocate a slug that does not collide with built-ins or existing uploads.
     *
     * @param string $requested
     * @return string
     */
    public static function allocate_unique_slug(string $requested): string {
        $base = self::normalize_slug($requested);
        $builtins = array_map(
            static fn($t) => strtolower((string) ($t['name'] ?? '')),
            self::list_builtin_themes()
        );
        $registry = self::get_registry();
        $existing = array_map('strtolower', array_keys($registry['uploaded']));
        $taken = array_merge($builtins, $existing);
        $slug = $base;
        $i = 2;
        while (in_array(strtolower($slug), $taken, true)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * Scan extracted dir for CSS files. style.css first if present.
     *
     * @param string $dir
     * @return string[]
     */
    private static function find_css_files(string $dir): array {
        $out = [];
        if (is_file($dir . '/style.css')) {
            $out[] = 'style.css';
        }
        $matches = glob($dir . '/*.css');
        if (is_array($matches)) {
            foreach ($matches as $file) {
                $base = basename($file);
                if (!in_array($base, $out, true)) {
                    $out[] = $base;
                }
            }
        }
        return $out;
    }

    /**
     * SHA-256 hash of a file, or empty string on failure.
     *
     * @param string $path
     * @return string
     */
    private static function hash_zip(string $path): string {
        $hash = @hash_file('sha256', $path);
        return is_string($hash) ? 'sha256:' . $hash : '';
    }

    /**
     * Recursively delete a directory tree.
     *
     * @param string $dir
     */
    public static function recursive_delete(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }
        if (is_link($dir) || is_file($dir)) {
            @unlink($dir);
            return;
        }
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::recursive_delete($dir . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($dir);
    }
}
