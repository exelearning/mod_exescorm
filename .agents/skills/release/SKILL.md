---
name: release
description: Prepare a mod_exescorm release (CHANGELOG entry, version checks, package audit) on a preparation branch. Asks for the target version and release date if not given. Never tags, publishes or commits without explicit authorization.
---

# Skill: Prepare a release

Prepare everything a maintainer needs to publish `mod_exescorm` vX.Y.Z, leaving the working tree ready for review. This skill **does not** create tags, publish GitHub releases, push or merge unless the user explicitly asks for it in the session.

## How releases are published (context)

* `version.php` keeps dev values (`9999999999` / `'dev'`) in git. **Never commit real values**: `make package RELEASE=X.Y.Z` and CI inject them into the ZIP only.
* `.github/workflows/check-editor-releases.yml` runs daily: when a new `exelearning/exelearning` release appears, it writes the tag to `.editor-version`, syncs the editor URL in `blueprint.json`, commits both to `main`, and publishes a GitHub release with the same tag (`vX.Y.Z`). That publication triggers `.github/workflows/release.yml`, which builds the editor and uploads `mod_exescorm-X.Y.Z.zip`.
* Therefore **do not edit `.editor-version` or the editor URL in `blueprint.json` by hand**: CI owns them, and changing them on `main` triggers a release.
* Plugin versions stay aligned with eXeLearning versions; a release can have no functional changes.

---

## 0. Inputs

Get from the request, or ask only if missing:

* **Version** (e.g. `4.0.6`) — use it verbatim; tags and headings use the `v` prefix (`v4.0.6`).
* **Release date** in `YYYY-MM-DD` — defaults to today only if the user does not give one.

## 1. Branch

Work on a preparation branch, never on `main`. If the user has not created one, suggest `prepare-vX.Y.Z`. Do not use a bare `vX.Y.Z` branch name: it would clash with the release tag.

## 2. Sanity checks

* `git status` — note untracked or modified files that must not end up in the release commits.
* `gh release view --repo exelearning/mod_exescorm --json tagName,publishedAt` — last published release; the new version must be greater and must not already exist as a tag (`git tag -l vX.Y.Z`, `gh release view vX.Y.Z`).
* `version.php` still holds dev values.
* `gh release view --repo exelearning/exelearning vX.Y.Z` — tell the user whether the matching editor release already exists (if not, the automatic release will happen when it does).

## 3. CHANGELOG entry

Follow `.agents/skills/changelog/SKILL.md` (steps 1–5) to draft the entry from PRs merged since the last release, with these overrides:

* Use the version and date from step 0, not today's date.
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

Do **not** commit unless asked. If the user wants commit commands, give them as copy-pasteable lines, one commit per logical change (e.g. skill/tooling changes separate from the CHANGELOG), and end each message with the attribution lines required by the session. Suggest the follow-up: push the branch, open a PR titled `Prepare vX.Y.Z` against `main`, and let the editor release trigger the automatic publication.
