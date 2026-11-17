#!/usr/bin/env bash
# The release of the library, from where it stands (build-tools release)

tool="$(dirname "$0")/../vendor/bin/build-tools"
[[ -x "$tool" ]] || { echo "build-tools is not installed: composer install" >&2; exit 1; }
exec "$tool" release "$@"
