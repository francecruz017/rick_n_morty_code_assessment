set -e

echo "Fixing var/ permissions for Symfony..."

if [ -d var ]; then
  chown -R www-data:www-data var
  chmod -R 775 var
fi

exec php-fpm