<?php

echo "Iniciando criacao do arquivo ZIP para producao...\n";

$zipName = 'projeto-producao.zip';
if (file_exists($zipName)) {
    unlink($zipName);
}

$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
    die("Erro: Nao foi possivel criar o arquivo ZIP {$zipName}.\n");
}

$rootPath = realpath(__DIR__);

// Diretorios a serem ignorados completamente (e seus subdiretorios)
$excludeDirs = ['.git', '.github', '.agent', '.kiro', 'node_modules', 'tests'];

// Arquivos especificos a serem ignorados
$excludeFiles = [
    'projeto-producao.zip',
    'preparar-producao.bat',
    'preparar-producao.sh',
    'build-zip.php',
    '.env',
    '.env.example',
    '.env.testing',
    '.gitattributes',
    '.gitignore',
    'compose.yaml',
    'phpunit.xml',
    'README.md',
    '.clinerules',
    '.editorconfig'
];

$count = 0;

// Funcao recursiva customizada para evitar ler diretorios indesejados ou sem permissao
function addFolderToZip($dir, $zip, $rootPath, $excludeDirs, $excludeFiles, &$count) {
    $handle = @opendir($dir);
    if (!$handle) {
        return; // Ignora silenciosamente se nao puder abrir
    }

    while (false !== ($f = readdir($handle))) {
        if ($f === '.' || $f === '..') {
            continue;
        }

        $filePath = $dir . '/' . $f;
        $relativePath = substr($filePath, strlen($rootPath) + 1);
        $relativePathNormalized = str_replace('\\', '/', $relativePath);

        // Pula links simbolicos (ex: public/storage deve ser criado no servidor via php artisan storage:link)
        if (is_link($filePath)) {
            continue;
        }

        if (is_dir($filePath)) {
            // Se o nome do diretorio estiver na lista de exclusao, ignora completamente
            if (in_array($f, $excludeDirs, true)) {
                continue;
            }
            addFolderToZip($filePath, $zip, $rootPath, $excludeDirs, $excludeFiles, $count);
        } else {
            // Se o arquivo nao puder ser lido, ignora
            if (!is_readable($filePath)) {
                continue;
            }
            // Se o arquivo estiver na lista de exclusao, ignora
            if (in_array($relativePathNormalized, $excludeFiles, true)) {
                continue;
            }
            $zip->addFile($filePath, $relativePathNormalized);
            $count++;
        }
    }
    closedir($handle);
}

addFolderToZip($rootPath, $zip, $rootPath, $excludeDirs, $excludeFiles, $count);

$zip->close();
echo "Sucesso: O arquivo {$zipName} foi criado com {$count} arquivos.\n";
echo "Os caminhos internos foram normalizados com barras Unix (/) para compatibilidade na Hostinger.\n";
