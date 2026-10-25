---
name: release
description: Prepare a mod_exescorm release (CHANGELOG entry, version checks, package audit) on a preparation branch. Asks for the target version and release date if not given. Never tags, publishes or commits without explicit authorization.
---

# Skill: Prepare a release

Prepare everything a maintainer needs to publish `mod_exescorm` vX.Y.Z, leaving the working tree ready for review. This skill **does not** create tags, publish GitHub releases, push or merge unless the user explicitly asks for it in the session.

## How releases are published (context)

* `version.php` keeps dev values (`9999999999` / `'dev'`) in git. **Never commit real values**: `make package RELEASE=X.Y.Z` and CI inject them into the ZIP only.
* `.github/workflows/check-editor-releases.yml` runs daily (and on `workflow_dispatch`). It compares the latest `exelearning/exelearning` release tag with `.editor-version` and does nothing if they match. When they differ, it builds the editor from that tag, runs `make package RELEASE=X.Y.Z` on the current `main`, writes the tag to `.editor-version`, syncs the editor URL in `blueprint.json`, commits both to `main`, and creates the GitHub release `vX.Y.Z` with `mod_exescorm-X.Y.Z.zip` attached (`softprops/action-gh-release`).
* That release is created with `GITHUB_TOKEN`, so it does **not** fire `release: published`: `.github/workflows/release.yml` does not run in the automatic path. `release.yml` only runs for releases published by a person or via `workflow_dispatch` (manual builds).
* **Do not edit `.editor-version` or the editor URL in `blueprint.json` by hand**: CI owns them. Setting `.editor-version` to the new tag makes the daily job report "Already up to date" and silently **skips** the automatic release.
* **Merge order matters**: the daily job packages whatever is on `main` when the editor tag appears. The `Prepare vX.Y.Z` PR must be merged **before** the daily job first runs after the matching eXeLearning release appears, or the ZIP ships without the new CHANGELOG entry. The job reads `releases/latest`, so eXeLearning prereleases do not trigger it.
* Plugin versions stay aligned with eXeLearning versions; a release can have no functional changes.

---

## 0. Inputs

Get from the request, or ask only if missing:

* **Version** (e.g. `4.0.6`) — use it verbatim; tags and headings use the `v` prefix (`v4.0.6`).
* **Release date** in `YYYY-MM-DD` — the day the matching eXeLearning release is expected (the automatic release happens then, not when this branch is prepared). If it is not known, ask; do not default to today. Remind the user to check the date again before merging and when the editor release lands, and to correct it if the editor release moves.

## 1. Branch

Work on a preparation branch, never on `main`. If the user has not created one, suggest `prepare-vX.Y.Z`. Do not use a bare `vX.Y.Z` branch name: it would clash with the release tag.

## 2. Sanity checks

* `git status` — note untracked or modified files that must not end up in the release commits.
* `gh release view --repo exelearning/mod_exescorm --json tagName,publishedAt` — last published release; the new version must be greater and must not already exist as a tag (`git tag -l vX.Y.Z`, `gh release view --repo exelearning/mod_exescorm vX.Y.Z`).
* `version.php` still holds dev values.
* `gh release view --repo exelearning/exelearning vX.Y.Z` — tell the user whether the matching editor release already exists. If it does not, the automatic release will happen the first day the job runs after it appears, so this branch must be merged before then. If it already exists, check whether `vX.Y.Z` was already published here without this CHANGELOG and warn the user.

## 3. CHANGELOG entry

Follow `.agents/skills/changelog/SKILL.md` (steps 1–5) to draft the entry from PRs merged since the last release, with these overrides:

* Use the version and date from step 0, not today's date.
* Write entries for site administrators and teachers (what changes for them), not descriptions of internal work.
* Heading format: `## vX.Y.Z – YYYY-MM-DD` (en dash), followed by a `---` separator line before the previous entry, as in the existing file.
* List the PRs you discarded and why.

## 4. Twin-plugin check

`mod_exeweb` usually releases the same version. Compare with its CHANGELOG and merged PRs (`gh pr list --repo exelearning/mod_exeweb --state merged --search "merged:>TIMESTAMP"`) and report fixes that landed in one plugin but not the other, so the user can decide whether to wait.

## 5. Package audit (optional, if requested or tooling is available)

* `make package RELEASE=X.Y.Z` must finish without errors and restore `version.php` to dev values afterwards (`git diff version.php` empty).
* Inspect the ZIP: top-level `exescorm/`; `.agents/`, `.claude/`, `AGENTS.md`, `CLAUDE.md`, `Makefile`, `composer.*`, `scripts/` and other `.distignore` entries excluded; `dist/static/` complete if the editor was built.
* Delete the generated ZIP afterwards: `.distignore` keeps it out of later packages, but it is not gitignored and must not be committed.

## 6. Report and hand off

Summarise PASS / FAIL / PENDING for each check, show the CHANGELOG block, and remind the user it is a draft to review.

Do **not** commit unless asked. If the user wants commit commands, give them as copy-pasteable lines, one commit per logical change (e.g. skill/tooling changes separate from the CHANGELOG), with no AI co-author or attribution trailers (this project does not use them). Suggest the follow-up: push the branch, open a PR titled `Prepare vX.Y.Z` against `main`, merge it **before** the matching eXeLearning release, and let the daily job publish the release. Remind the user to confirm the CHANGELOG date when the editor release lands.
