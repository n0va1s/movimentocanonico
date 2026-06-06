# Inicializa a compressao do .NET
Add-Type -AssemblyName System.IO.Compression
[void][System.Reflection.Assembly]::LoadWithPartialName("System.IO.Compression.FileSystem")

Write-Host "Iniciando criacao do arquivo ZIP para producao..."

$zipName = "projeto-producao.zip"
$zipPath = Join-Path (Get-Location) $zipName

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$rootPath = (Get-Location).Path
$excludeDirs = @('.git', '.github', '.agent', '.kiro', 'node_modules', 'tests')
$excludeFiles = @(
    'projeto-producao.zip',
    'preparar-producao.bat',
    'preparar-producao.sh',
    'build-zip.php',
    'build-zip.ps1',
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
)

$zipStream = [System.IO.File]::Create($zipPath)
$archive = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

$count = 0

# Funcao recursiva customizada para evitar ler diretorios indesejados ou sem permissao
# O uso de -Force garante que pastas ocultas ou com atributos do sistema (como vendor) nao sejam puladas.
function Add-FilesToZip($dir) {
    $items = Get-ChildItem -Path $dir -Force
    foreach ($item in $items) {
        if ($item.PSIsContainer) {
            # Se o diretorio estiver na lista de exclusao, ignora completamente
            if ($excludeDirs -contains $item.Name) {
                continue
            }
            Add-FilesToZip $item.FullName
        } else {
            $filePath = $item.FullName
            $relativePath = $filePath.Substring($rootPath.Length + 1)
            
            # Normaliza o separador de caminhos do Windows (\) para barras Unix (/)
            $relativePathNormalized = $relativePath.Replace('\', '/')
            
            # Se o arquivo estiver na lista de exclusao, ignora
            if ($excludeFiles -contains $relativePathNormalized) {
                continue
            }
            
            # Adiciona o arquivo ao ZIP mantendo a estrutura normalizada com barras normais
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $filePath, $relativePathNormalized)
            $script:count++
        }
    }
}

Add-FilesToZip $rootPath

$archive.Dispose()
$zipStream.Close()

Write-Host "Sucesso: O arquivo $zipName foi criado com $count arquivos."
Write-Host "Os caminhos internos foram normalizados com barras Unix (/) para compatibilidade na Hostinger."
