# Inicializa a compressao do .NET
Add-Type -AssemblyName System.IO.Compression
[void][System.Reflection.Assembly]::LoadWithPartialName("System.IO.Compression.FileSystem")

Write-Host "Iniciando criacao do arquivo ZIP para producao..."

$script:rootPath = (Get-Location).Path
$parentPath = Split-Path -Parent $script:rootPath

$zipName = "projeto-producao.zip"
$zipPath = Join-Path $parentPath $zipName

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$script:excludeDirs = @('.git', '.github', '.agent', '.kiro', 'node_modules', 'tests')
$script:excludeFiles = @(
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
$script:archive = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

$script:count = 0

# Funcao recursiva customizada para evitar ler diretorios indesejados ou sem permissao
# O uso de -Force garante que pastas ocultas ou com atributos do sistema (como vendor) nao sejam puladas.
function Add-FilesToZip($dir, $relativePathPrefix) {
    $items = Get-ChildItem -Path $dir -Force
    foreach ($item in $items) {
        # Determina o caminho relativo deste item de forma direta
        if ([string]::IsNullOrEmpty($relativePathPrefix)) {
            $itemRelativePath = $item.Name
        } else {
            $itemRelativePath = "$relativePathPrefix/$($item.Name)"
        }

        if ($item.PSIsContainer) {
            # Se o diretorio estiver na lista de exclusao, ignora completamente
            if ($script:excludeDirs -contains $item.Name) {
                continue
            }
            Add-FilesToZip $item.FullName $itemRelativePath
        } else {
            $filePath = $item.FullName
            
            # Normaliza o separador de caminhos do Windows (\) para barras Unix (/)
            $relativePathNormalized = $itemRelativePath.Replace('\', '/')
            
            # Se o arquivo estiver na lista de exclusao, ignora
            if ($script:excludeFiles -contains $relativePathNormalized) {
                continue
            }
            
            # Adiciona o arquivo ao ZIP mantendo a estrutura normalizada com barras normais
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($script:archive, $filePath, $relativePathNormalized)
            $script:count++
        }
    }
}

Add-FilesToZip $script:rootPath ""

$script:archive.Dispose()
$zipStream.Close()

Write-Host "Sucesso: O arquivo $zipName foi criado com $script:count arquivos na pasta acima."
Write-Host "Os caminhos internos foram normalizados com barras Unix (/) para compatibilidade na Hostinger."
