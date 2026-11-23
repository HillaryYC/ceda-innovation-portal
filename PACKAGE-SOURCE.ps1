param([string]$Output = 'submission-pack/CEDA-PORTAL-SOURCE.zip')

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path $PSScriptRoot).Path
$outputPath = [System.IO.Path]::GetFullPath((Join-Path $root $Output))
if (-not $outputPath.StartsWith($root + [System.IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Output must be inside the project directory.'
}

$rootFiles = @('README.md', '.gitignore', '.dockerignore', 'Dockerfile', 'Caddyfile', 'railway.json', 'PACKAGE-SOURCE.ps1')
$excludedDirectories = @('node_modules', 'vendor', 'dist', '.git', '.impeccable', 'tools', 'submission-pack')
$excludedNames = @('.env', '.env.local', '.phpunit.result.cache', 'database.sqlite', 'database.sqlite-journal')
$files = @()
foreach ($name in $rootFiles) { $files += Get-Item -LiteralPath (Join-Path $root $name) }
foreach ($folder in @('frontend', 'backend', 'docs')) {
    $files += Get-ChildItem -LiteralPath (Join-Path $root $folder) -Recurse -File -Force | Where-Object {
        $relative = [System.IO.Path]::GetRelativePath($root, $_.FullName).Replace('\', '/')
        $parts = $relative.Split('/')
        -not ($parts | Where-Object { $_ -in $excludedDirectories }) -and
        $_.Name -notin $excludedNames -and
        -not ($_.Name -like '.env.*' -and $_.Name -notlike '*.example') -and
        -not ($relative -like 'backend/storage/*' -and $relative -notlike 'backend/storage/*.gitignore' -and $relative -notlike 'backend/storage/*/.gitignore') -and
        -not ($relative -like 'backend/public/build/*') -and
        -not ($relative -like 'backend/README*.md') -and
        -not ($relative -like 'backend/bootstrap/cache/*' -and $_.Name -ne '.gitignore')
    }
}

New-Item -ItemType Directory -Force -Path ([System.IO.Path]::GetDirectoryName($outputPath)) | Out-Null
Add-Type -AssemblyName System.IO.Compression
$stream = [System.IO.File]::Create($outputPath)
try {
    $zip = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
    try {
        foreach ($file in $files) {
            $relative = [System.IO.Path]::GetRelativePath($root, $file.FullName).Replace('\', '/')
            $entry = $zip.CreateEntry($relative, [System.IO.Compression.CompressionLevel]::Optimal)
            $input = [System.IO.File]::OpenRead($file.FullName)
            try { $target = $entry.Open(); try { $input.CopyTo($target) } finally { $target.Dispose() } }
            finally { $input.Dispose() }
        }
    } finally { $zip.Dispose() }
} finally { $stream.Dispose() }
Write-Host "Packed $($files.Count) files into $outputPath"
Write-Host "Size: $([math]::Round((Get-Item -LiteralPath $outputPath).Length / 1MB, 2)) MB"
