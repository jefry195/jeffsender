#!/bin/bash
# ============================================================
# Script: jalankan_queue_worker.sh
# Deskripsi: Menjalankan Laravel Queue Worker untuk Jeffsender
# Cara pakai di VPS/Production:
#   chmod +x jalankan_queue_worker.sh
#   ./jalankan_queue_worker.sh
# Atau pakai supervisor (direkomendasikan di production)
# ============================================================

PHP_BIN=$(which php || echo "/usr/bin/php")
ARTISAN_PATH="/var/www/jeffsender/artisan"

# Jika path artisan tidak ada, coba deteksi otomatis
if [ ! -f "$ARTISAN_PATH" ]; then
    ARTISAN_PATH="$(dirname "$0")/artisan"
fi

echo "========================================"
echo "  Jeffsender - Queue Worker (Linux/VPS)"
echo "  PHP     : $PHP_BIN"
echo "  Artisan : $ARTISAN_PATH"
echo "========================================"
echo ""

while true; do
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Queue worker dimulai..."
    $PHP_BIN "$ARTISAN_PATH" queue:work database \
        --timeout=900 \
        --tries=1 \
        --sleep=3 \
        --max-jobs=50
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Queue worker berhenti. Restart dalam 5 detik..."
    sleep 5
done
