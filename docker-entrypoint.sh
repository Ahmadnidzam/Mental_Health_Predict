#!/bin/bash
# Entrypoint script - jalan setiap kali container start
set -e

cd /var/www/html

echo "==> Caching config..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Menunggu database siap..."
# Retry connect ke MySQL maksimum 30 detik
for i in $(seq 1 30); do
    if php artisan migrate:status >/dev/null 2>&1; then
        echo "    Database tersambung."
        break
    fi
    echo "    [$i/30] Database belum siap, tunggu 1 detik..."
    sleep 1
done

echo "==> Menjalankan migration..."
php artisan migrate --force

# Jalankan training hanya kalau model.pkl belum ada
if [ ! -f storage/models/knn_model.pkl ]; then
    echo "==> Training model ML (sekali saja, butuh beberapa menit)..."
    python3 storage/models/train_models.py

    echo "==> Seed metric ke database..."
    php artisan db:seed --class=ModelMetricSeeder --force
else
    echo "==> Model .pkl sudah ada, skip training."
fi

# Pastikan ownership benar setelah training (file .pkl baru)
chown -R www-data:www-data storage/models /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "==> Starting Apache..."
exec "$@"
