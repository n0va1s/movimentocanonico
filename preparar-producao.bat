@echo off
echo [1/6] Compilando assets do Front-end...
call npm install
call npm run build

echo [2/6] Limpando caches antigos do Laravel...
call php artisan clear-compiled
call php artisan cache:clear
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear

echo [3/6] Otimizando dependencias do Composer...
call composer install --optimize-autoloader --no-dev

echo [4/6] Gerando novos caches de producao...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache

echo [5/6] Criando arquivo ZIP para a Hostinger...
powershell -ExecutionPolicy Bypass -File build-zip.ps1

echo.
echo Executado com sucesso! O arquivo projeto-producao.zip esta pronto.
echo Dica: Caso queira restaurar as dependencias locais de desenvolvimento, execute:
echo composer install
echo.
pause
