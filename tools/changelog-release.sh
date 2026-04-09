#!/usr/bin/env bash
# Insert changelog fragments under the current version heading in ChangeLog.
#
# Usage: tools/changelog-release.sh
#
# Reads the version from the first line of ChangeLog, collects all *.md files
# from changelog.d/ (except README.md), inserts them as "  * <line>" entries
# directly after the version heading, then deletes the fragment files.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FRAGMENTS_DIR="$SCRIPT_DIR/../changelog.d"
CHANGELOG="$SCRIPT_DIR/../ChangeLog"

version=$(head -1 "$CHANGELOG")

entries=""
for f in "$FRAGMENTS_DIR"/*.md; do
    [ -f "$f" ] || continue
    [ "$(basename "$f")" = "README.md" ] && continue
    line=$(head -1 "$f")
    [ -n "$line" ] && entries="${entries}  * ${line}\n"
done

if [ -z "$entries" ]; then
    echo "No changelog fragments found in $FRAGMENTS_DIR/" >&2
    exit 1
fi

# Insert entries after the first line (version heading) of ChangeLog
tmp=$(mktemp)
head -1 "$CHANGELOG" > "$tmp"
printf '%b' "$entries" >> "$tmp"
tail -n +2 "$CHANGELOG" >> "$tmp"
mv "$tmp" "$CHANGELOG"

# Delete consumed fragment files (git rm stages the removal for the release commit)
for f in "$FRAGMENTS_DIR"/*.md; do
    [ -f "$f" ] || continue
    [ "$(basename "$f")" = "README.md" ] && continue
    git rm -f "$f"
done

count=$(printf '%b' "$entries" | grep -c "^  \* ")
echo "ChangeLog updated for $version ($count entries added)"
