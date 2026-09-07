#!/usr/bin/env bash
#
# Sync the quizextensionmanager plugin pair from this git checkout into a
# real Moodle codebase, and fix ownership so the web server (www-data) can
# read the copied files. This exists because symlinking the plugin in
# instead of copying it broke several build tools (ESLint's config
# resolution, its Babel parser's module resolution, and Grunt/Rollup's
# AMD module-name computation) -- all of them compute paths relative to
# the file's *real* location, which a symlink pointing outside the Moodle
# tree defeats.
#
# Run this as root (it chowns the copied files to damon:www-data).
# Re-run it after every `git pull` in this checkout to propagate changes.
#
# Usage: ./sync-to-moodle.sh

set -euo pipefail

# Edit these if your checkout or Moodle install live somewhere else.
SRC_ROOT="/home/damon/moodledev/moodle_activityextension"
MOODLE_ROOT="/var/www/html/moodle/public"
OWNER="damon:www-data"

if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root (it chowns files to $OWNER)." >&2
    exit 1
fi

PLUGINS=(
    "local/quizextensionmanager"
    "mod/quiz/accessrule/quizextensionmanager"
)

for plugin in "${PLUGINS[@]}"; do
    src="$SRC_ROOT/$plugin"
    dest="$MOODLE_ROOT/$plugin"

    if [ ! -d "$src" ]; then
        echo "Source directory not found, aborting: $src" >&2
        exit 1
    fi

    mkdir -p "$dest"

    echo "Syncing $src -> $dest"
    rsync -a --delete "$src/" "$dest/"

    echo "Fixing ownership on $dest"
    chown -R "$OWNER" "$dest"
done

echo "Done."
echo "If anything under amd/src changed, rebuild it as your normal user (not root):"
echo "  cd $(dirname "$MOODLE_ROOT") && npx grunt amd"
