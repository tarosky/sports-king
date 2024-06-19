#! /usr/bin/env bash

# Clone app/Tarosky/Common directory in bk-themes.
rsync -av --delete ../bk-themes/akagi/app/Tarosky/Common/ ./src/

# Clone functions/common-*.php in bk-themes.
rsync -av --delete --include 'common-*.php' --exclude '*' ../bk-themes/akagi/functions/ ./functions/
