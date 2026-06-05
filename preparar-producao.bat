@echo off
echo [1/5] Compilando assets do Front-end...
call npm install
call npm run build

echo [2/5] Limpando caches antigos do Laravel...
call php artisan clear-compiled
call php artisan cache:clear
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear

echo [3/5] Otimizando dependencias do Composer...
call composer install --optimize-autoloader --no-dev

echo [4/5] Gerando novos caches de producao...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache

echo [5/5] Removendo pastas locais de desenvolvimento...
if exist node_modules rmdir /s /q node_modules
if exist tests rmdir /s /q tests

echo [6/5] Criando arquivo ZIP para a Hostinger...
if exist projeto-producao.zip del projeto-producao.zip
powershell -Command "Compress-Archive -Path (Get-ChildItem -Path . -Exclude 'projeto-producao.zip', '.git', '.env') -DestinationPath 'projeto-producao.zip' -Force"

echo Executado com sucesso! O arquivo projeto-producao.zip está pronto.
pause
