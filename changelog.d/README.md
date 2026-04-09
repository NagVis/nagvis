# Changelog Fragments

Each fix or feature adds **one file** to this directory instead of editing
`ChangeLog` directly. This prevents merge conflicts since every change lives
in its own file.

## Filename convention

```
<type>-<short-description>.md
```

Types: `fix`, `feat`, `security`, `deprecation`, `removal`

Examples: `fix-worldmap-lines.md`, `feat-dark-mode.md`

## File content

A single line — the changelog entry text, without the leading `  * `:

```
FIX: Worldmap lines with both endpoints relative disappear after zoom
```

## Release process

```
tools/changelog-release.sh
```

The script inserts all fragments under the current version heading in
`ChangeLog` and then deletes the fragment files. Run it as part of the
release commit.
