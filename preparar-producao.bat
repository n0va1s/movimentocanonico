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

echo [5/6] Removendo pastas locais de desenvolvimento...
if exist node_modules rmdir /s /q node_modules
if exist tests rmdir /s /q tests

echo [6/6] Criando arquivo ZIP para a Hostinger...
if exist projeto-producao.zip del projeto-producao.zip

:: Cria uma lista temporária excluindo o que não deve ir para o ZIP
echo .clinerules >> excluir.txt
echo .editorconfig >> excluir.txt
echo .env >> excluir.txt
echo .env.example >> excluir.txt
echo .env.testing >> excluir.txt
echo .gitattributes >> excluir.txt
echo .gitignore >> excluir.txt
echo compose.yaml >> excluir.txt
echo phpunit.html >> excluir.txt
echo README >> excluir.txt
echo projeto-producao.zip >> excluir.txt
echo preparar-producao.bat >> excluir.txt
echo preparar-producao.sh >> excluir.txt
echo excluir.txt >> excluir.txt

:: Utiliza o comando tar nativo do Windows para criar o ZIP sem travar em arquivos abertos
tar -a -c -f projeto-producao.zip --exclude-from=excluir.txt *

:: Remove o arquivo temporário de exclusão
del excluir.txt

echo.
echo Executado com sucesso! O arquivo projeto-producao.zip esta pronto.
pause
