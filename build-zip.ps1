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

# Obtem recursivamente todos os arquivos
$files = Get-ChildItem -Path $rootPath -Recurse -File

foreach ($file in $files) {
    $filePath = $file.FullName
    $relativePath = $filePath.Substring($rootPath.Length + 1)
    
    # Normaliza o separador de caminhos do Windows (\) para barras Unix (/)
    $relativePathNormalized = $relativePath.Replace('\', '/')
    
    # Verifica se algum diretorio pai ou o proprio arquivo esta na lista de exclusao
    $parts = $relativePathNormalized.Split('/')
    $exclude = $false
    foreach ($part in $parts) {
        if ($excludeDirs -contains $part) {
            $exclude = $true
            break;
        }
    }
    
    if ($excludeFiles -contains $relativePathNormalized) {
        $exclude = $true
    }
    
    if ($exclude) {
        continue
    }
    
    # Adiciona o arquivo ao ZIP mantendo a estrutura normalizada com barras normais
    [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $filePath, $relativePathNormalized)
    $count++
}

$archive.Dispose()
$zipStream.Close()

Write-Host "Sucesso: O arquivo $zipName foi criado com $count arquivos."
Write-Host "Os caminhos internos foram normalizados com barras Unix (/) para compatibilidade na Hostinger."
