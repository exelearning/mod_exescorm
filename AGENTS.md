# AGENTS.md

This file provides guidance to coding agents (Claude Code, Codex, etc.) when working with code in this repository.

## Project Overview

Moodle activity module (`mod_exescorm`) — an eXeLearning-aware fork of `mod_scorm` that lets teachers author SCORM content with eXeLearning. Packages can be uploaded locally, edited via eXeLearning Online (remote editor), or authored with the embedded static editor.

**Component**: `mod_exescorm`
**Moodle compatibility**: 4.2+
**License**: GNU GPL v3+

## Common Commands

```bash
# Development environment (Docker-based)
make up              # Start containers interactively
make upd             # Start containers in background
make down            # Stop containers
make shell           # Shell into Moodle container
make clean           # Stop containers + remove volumes

# PHP dependencies
make install-deps    # composer install

# Code quality
make lint            # PHP CodeSniffer (Moodle standard)
make fix             # Auto-fix CodeSniffer violations
make phpmd           # PHP Mess Detector

# Testing
make test            # PHPUnit tests
make behat           # Behat BDD tests

# Embedded editor
make build-editor    # Fetch exelearning source + build to dist/static/
make clean-editor    # Remove built editor artifacts

# Packaging
make package RELEASE=1.2.3   # Create distributable ZIP
```

## Code Standards

- **PHP**: Moodle coding standard enforced via PHP CodeSniffer (`make lint`/`make fix`)
- **Strings**: All UI strings in `lang/{ca,en,es,eu,gl}/exescorm.php` — use `get_string('key', 'mod_exescorm')`
- **JS**: AMD modules in `amd/src/`, compiled to `amd/build/`

## Agent Skills

Project-level skills live in `.agents/skills/` and are invoked with `/skill-name` in Claude Code (or equivalent mechanism in other agents).

| Skill | Description |
|-------|-------------|
| `changelog` | Draft the next CHANGELOG entry from merged GitHub PRs |

## Packaging & Release

- `make package RELEASE=X.Y.Z` updates `version.php`, creates a distributable ZIP excluding everything listed in `.distignore`, then restores dev values
- GitHub Actions `release.yml` triggers on git tags: fetches editor, builds, packages, uploads to GitHub Release
- `check-editor-releases.yml` runs daily to auto-release when new editor versions appear

## Twin-plugin checks (mod_exescorm ↔ mod_exeweb)

`mod_exescorm` and [`mod_exeweb`](https://github.com/exelearning/mod_exeweb) share large amounts of code, history, and bug surface (embedded editor, action bar, packaging pipeline, online callbacks, etc.). **Before closing a fix, always cross-check the sibling plugin:**

1. **Search the sibling repo for a matching issue.** Examples:
   - `gh issue list --repo exelearning/mod_exeweb --search "<keywords>"`
   - `gh issue list --repo exelearning/mod_exeweb --state all --search "<keywords>"` (also closed)
   If a matching issue exists, plan to fix both at once and link them in the PR bodies.
2. **Even if no twin issue is open**, audit the sibling for the same root cause: most bugs reproduce on both sides because the relevant code is intentionally near-identical.
3. When the bug applies to both, **open a PR in each repo** with the parallel fix and **cross-reference the sibling PR** in the body (e.g. `Sibling PR: exelearning/mod_exeweb#50`). Use parallel branch names and commit messages so the diffs are easy to compare.
4. If after auditing only one plugin is affected, document why in the PR body so a future reader doesn't repeat the search.
