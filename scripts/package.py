#!/usr/bin/env python3
"""Creates a distributable ZIP for mod_exescorm, respecting .distignore."""
import sys
import os
import zipfile
import fnmatch


def main():
    if len(sys.argv) < 3:
        print("Usage: package.py <release> <plugin_name>", file=sys.stderr)
        sys.exit(1)

    release = sys.argv[1]
    plugin_name = sys.argv[2]

    with open('.distignore') as f:
        patterns = [
            line.strip().rstrip('/')
            for line in f
            if line.strip() and not line.startswith('#')
        ]

    def is_excluded(relpath):
        relpath = relpath.replace(os.sep, '/')
        top = relpath.split('/')[0]
        return any(
            fnmatch.fnmatch(top, p) or fnmatch.fnmatch(relpath, p)
            for p in patterns
        )

    output = f'{plugin_name}-{release}.zip'
    # strict_timestamps=False clamps pre-1980 mtimes (common in extracted ZIPs / git objects)
    with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED, strict_timestamps=False) as zf:
        for root, dirs, files in os.walk('.'):
            rel_root = os.path.relpath(root, '.').replace(os.sep, '/')
            dirs[:] = [
                d for d in dirs
                if not is_excluded(f'{rel_root}/{d}' if rel_root != '.' else d)
            ]
            for f in files:
                rel = f'{rel_root}/{f}' if rel_root != '.' else f
                if not is_excluded(rel):
                    zf.write(os.path.join(root, f), f'exescorm/{rel}')


if __name__ == '__main__':
    main()
