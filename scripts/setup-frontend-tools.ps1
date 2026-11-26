param(
    [ValidateSet('Skills', 'Penpot', 'ScreenshotToCode')]
    [string[]] $Tools = @('Skills', 'Penpot', 'ScreenshotToCode')
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$skillsRoot = Join-Path $projectRoot '.agents/skills'
$toolsRoot = Join-Path $projectRoot '.frontend-tools'

function Invoke-Git {
    & git @args
    if ($LASTEXITCODE -ne 0) {
        throw "Git failed: $($args -join ' ')"
    }
}

function Get-SkillDestination($Path) {
    $name = Split-Path -Leaf $Path
    if ($name -eq 'taste-skill') { $name = 'design-taste-frontend' }
    Join-Path $skillsRoot $name
}

function Install-Skills($Repository, $Revision, $Paths) {
    $pending = @($Paths | Where-Object {
        $destination = Get-SkillDestination $_
        if (Test-Path $destination) {
            if (!(Test-Path (Join-Path $destination 'SKILL.md') -PathType Leaf)) {
                throw "Incomplete skill: $destination"
            }
            Write-Host "Already installed: $destination"
            return $false
        }
        return $true
    })
    if (!$pending.Count) { return }

    $checkout = Join-Path $toolsRoot ([guid]::NewGuid().ToString())
    New-Item -ItemType Directory -Path $checkout | Out-Null
    try {
        Invoke-Git -C $checkout init --quiet
        Invoke-Git -C $checkout remote add origin "https://github.com/$Repository.git"
        Invoke-Git -C $checkout sparse-checkout set @pending
        Invoke-Git -C $checkout fetch --quiet --depth=1 --filter=blob:none origin $Revision
        Invoke-Git -C $checkout checkout --quiet --detach FETCH_HEAD
        foreach ($path in $pending) {
            $source = Join-Path $checkout $path
            $destination = Get-SkillDestination $path
            $skillFile = Join-Path $source 'SKILL.md'
            if (!(Test-Path $skillFile -PathType Leaf)) {
                throw "SKILL.md missing from $Repository/$path"
            }
            if ($path -eq '.claude/skills/ui-ux-pro-max') {
                $content = [IO.File]::ReadAllText($skillFile)
                $content = $content.Replace('${CLAUDE_PLUGIN_ROOT}/.claude/skills/ui-ux-pro-max/', '.agents/skills/ui-ux-pro-max/')
                [IO.File]::WriteAllText($skillFile, $content)
            }
            Move-Item -LiteralPath $source -Destination $destination
            Write-Host "Installed: $destination"
        }
    } finally {
        $resolvedCheckout = (Resolve-Path -LiteralPath $checkout).Path
        $resolvedRoot = (Resolve-Path -LiteralPath $toolsRoot).Path + [IO.Path]::DirectorySeparatorChar
        if ($resolvedCheckout.StartsWith($resolvedRoot, [StringComparison]::OrdinalIgnoreCase)) {
            Remove-Item -LiteralPath $resolvedCheckout -Recurse -Force
        }
    }
}

New-Item -ItemType Directory -Path $toolsRoot -Force | Out-Null

if ('Skills' -in $Tools) {
    New-Item -ItemType Directory -Path $skillsRoot -Force | Out-Null
    Install-Skills 'proflead/codex-skills-library' 'a05260c8832b11054b0cc311b51e0fd1afb1a62a' @(
        'skills/frontend/accessibility-basic-check'
        'skills/frontend/css-layout-helper'
    )
    Install-Skills 'pbakaus/impeccable' '2fc5e1459efbbf06d92c7306517c286bf12490cf' @('.agents/skills/impeccable')
    Install-Skills 'Leonxlnx/taste-skill' 'd1a2bb41d927083ac7118f60cfc9cdf711810ea0' @('skills/taste-skill')
    Install-Skills 'anthropics/skills' 'e4bb5d960d87126ff3b3a8af1a104b7db67560ba' @('skills/frontend-design')
    Install-Skills 'nextlevelbuilder/ui-ux-pro-max-skill' '01706130e32f2af1d12923cce8d64a85fd76a3cd' @('.claude/skills/ui-ux-pro-max')
    Install-Skills 'openai/skills' '49f948faa9258a0c61caceaf225e179651397431' @(
        'skills/.curated/figma-create-design-system-rules'
        'skills/.curated/figma-implement-design'
    )
}

if ('Penpot' -in $Tools) {
    $penpotVersion = '2.19.5'
    $penpotRoot = Join-Path $toolsRoot 'penpot'
    New-Item -ItemType Directory -Path $penpotRoot -Force | Out-Null
    $composePath = Join-Path $penpotRoot 'compose.yaml'
    if (!(Test-Path $composePath)) {
        $compose = (Invoke-WebRequest -UseBasicParsing "https://raw.githubusercontent.com/penpot/penpot/$penpotVersion/docker/images/docker-compose.yaml").Content
        $compose = $compose.Replace('${PENPOT_VERSION:-2.18}', $penpotVersion)
        $compose = $compose.Replace('- 9001:8080', '- "127.0.0.1:9001:8080"')
        $compose = $compose.Replace('- "8025:8025"', '- "127.0.0.1:9025:8025"')
        Set-Content -LiteralPath $composePath -Value $compose -Encoding ascii
    }
}

if ('ScreenshotToCode' -in $Tools) {
    $screenshotRoot = Join-Path $toolsRoot 'screenshot-to-code'
    if (!(Test-Path (Join-Path $screenshotRoot 'backend/Dockerfile'))) {
        New-Item -ItemType Directory -Path $screenshotRoot -Force | Out-Null
        Invoke-Git -C $screenshotRoot init --quiet
        Invoke-Git -C $screenshotRoot fetch --quiet --depth=1 https://github.com/abi/screenshot-to-code.git f2e5bbdd1dd5d93c259548d9bccf54e2a5f06a30
        Invoke-Git -C $screenshotRoot checkout --quiet --detach FETCH_HEAD
    }
    $envPath = Join-Path $screenshotRoot '.env'
    if (!(Test-Path $envPath)) {
        New-Item -ItemType File -Path $envPath | Out-Null
    }
    foreach ($service in @('backend', 'frontend')) {
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot "screenshot-to-code.$service.Dockerfile") -Destination (Join-Path $screenshotRoot "$service/Dockerfile.local") -Force
    }
}
