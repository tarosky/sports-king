#! /usr/bin/env bash

# Clone app/Tarosky/Common directory in bk-themes.
rsync -av --delete ../bk-themes/akagi/app/Tarosky/Common/ ./app/

# Clone functions/common-*.php in bk-themes.
rsync -av --delete --include 'common-*.php' --exclude '*' ../bk-themes/akagi/functions/ ./functions/

# Clone assets/js/admin in bk-themes.
rsync -av --delete ../bk-themes/akagi/assets/js/admin/ ./assets/js/admin/
rsync -av --delete ../bk-themes/akagi/src/js/admin/ ./src/js/admin/
rsync -av --delete ../bk-themes/akagi/assets/css/admin/ ./assets/css/admin/
rsync -av --delete ../bk-themes/akagi/src/sass/admin/ ./src/scss/admin/
