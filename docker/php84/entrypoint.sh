#!/bin/sh
set -e

# Run composer install if composer.json exists and vendor is missing/incomplete
# if [ -f "/srv/alina/composer.json" ]; then
#     if [ ! -d "/srv/alina/vendor" ] || [ ! -f "/srv/alina/vendor/autoload.php" ]; then
#         echo "Installing Composer dependencies..."
#         cd /srv/alina
#         composer install --no-interaction --optimize-autoloader
#         echo "Composer dependencies installed successfully!"
#     fi
# fi

# Execute the main command
exec "$@"