#!/bin/sh

_me=$(realpath "$0")
_dir=$(dirname "$_me")
_test=${1}

[ -n "$_test" ] && _test=":$_test"
_test="test$_test"

composer run-script "$_test"

inotifywait -q -r -m -e modify "$_dir/" | \
while read path action file; do
    clear
    echo "Action: $action @ `date`"
    echo
    composer run-script "$_test"
done
