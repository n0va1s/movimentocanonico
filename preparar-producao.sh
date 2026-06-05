#!/bin/bash

echo "[1/5] Compilando assets do Front-end..."
npm install && npm run build

echo "[2/5] Limpando caches antigos do Laravel..."
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "[3/5] Otimizando dependencias do Composer..."
composer install --optimize-autoloader --no-dev

echo "[4/5] Gerando novos caches de producao..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/5] Removendo pastas locais de desenvolvimento..."
rm -rf node_modules
rm -rf tests

echo "[6/5] Criando arquivo ZIP para a Hostinger..."
rm -f projeto-producao.zip
zip -r projeto-producao.zip . -x "*.git*" "node_modules/*" "tests/*" "projeto-producao.zip" ".env"

echo "Executado com sucesso! O arquivo projeto-producao.zip está pronto."
