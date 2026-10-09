#!/bin/sh
set -eu
php backend/tests/energy_test.php
node backend/tests/api_client_test.cjs
find backend public/api -name '*.php' -exec php -l {} \; | grep -v 'No syntax errors' || true
for js in public/assets/js/*.js; do node --check "$js"; done
printf '\nStatic checks completed. DB integration requires pdo_mysql + MySQL 8.\n'
