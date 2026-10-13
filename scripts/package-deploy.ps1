<#
.SYNOPSIS
    Script de preparación de paquete de despliegue para Hosting Compartido (cPanel / Apache).

.DESCRIPTION
    Compila los assets de Vite en local, limpia cachés locales, y crea un archivo ZIP en dist/
    excluyendo carpetas innecesarias para producción (node_modules, .git, tests, .env, storage/logs, etc.).

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\scripts\package-deploy.ps1
#>

$ErrorActionPreference = "Stop"
$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $ProjectRoot

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " JC Autopartes — Preparación de Paquete para Producción   " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Compilación de Assets de Vite
Write-Host "`n[1/4] Compilando assets de Vite para producción (npm run build)..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Error "Error durante la compilación de Vite."
    exit 1
}

# 2. Creación del directorio de salida
$DistDir = Join-Path $ProjectRoot "dist"
if (-not (Test-Path $DistDir)) {
    New-Item -ItemType Directory -Path $DistDir | Out-Null
}

$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$ZipFileName = "jc-autopartes-deploy-$Timestamp.zip"
$ZipPath = Join-Path $DistDir $ZipFileName

# 3. Directorio Temporal de Empaquetado
Write-Host "`n[2/4] Copiando archivos del proyecto (excluyendo desarrollo)..." -ForegroundColor Yellow
$TempPackageDir = Join-Path $DistDir "temp_package_$Timestamp"
if (Test-Path $TempPackageDir) {
    Remove-Item -Recurse -Force $TempPackageDir
}
New-Item -ItemType Directory -Path $TempPackageDir | Out-Null

# Lista de exclusiones usando Robocopy
$ExcludeDirs = @(
    "node_modules",
    ".git",
    ".gemini",
    "dist",
    "tests",
    "storage\logs",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views"
)

$ExcludeFiles = @(
    ".env",
    ".env.backup",
    ".gitattributes",
    ".gitignore",
    "*.log",
    "package-lock.json",
    "phpunit.xml"
)

$RobocopyArgs = @(
    $ProjectRoot,
    $TempPackageDir,
    "/E",
    "/XD"
) + $ExcludeDirs + @("/XF") + $ExcludeFiles

& robocopy @RobocopyArgs | Out-Null

# Asegurar que las carpetas de storage y bootstrap existan vacías con .gitignore
$RequiredDirs = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)

foreach ($dir in $RequiredDirs) {
    $targetPath = Join-Path $TempPackageDir $dir
    if (-not (Test-Path $targetPath)) {
        New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
    }
    $gitKeep = Join-Path $targetPath ".gitignore"
    if (-not (Test-Path $gitKeep)) {
        Set-Content -Path $gitKeep -Value "*`n!.gitignore"
    }
}

# 4. Generación del archivo ZIP
Write-Host "`n[3/4] Generando archivo comprimido ZIP ($ZipFileName)..." -ForegroundColor Yellow
Compress-Archive -Path "$TempPackageDir\*" -DestinationPath $ZipPath -Force

# 5. Limpieza
Write-Host "`n[4/4] Limpiando archivos temporales..." -ForegroundColor Yellow
Remove-Item -Recurse -Force $TempPackageDir

$FileSizeMB = [math]::Round(((Get-Item $ZipPath).Length / 1MB), 2)
Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host " ¡Paquete generado exitosamente!                         " -ForegroundColor Green
Write-Host " Archivo: $ZipPath ($FileSizeMB MB)                      " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "`nPasos para desplegar en tu hosting compartido:" -ForegroundColor Cyan
Write-Host "  1. Sube este archivo .zip a tu servidor (cPanel / Administrador de archivos)."
Write-Host "  2. Extrae el contenido en el directorio del proyecto (ej. /home/usuario/jc-autopartes)."
Write-Host "  3. Configura tu archivo .env en el servidor (copia desde .env.example)."
Write-Host "  4. Ejecuta: php artisan migrate --force --seed"
Write-Host "  5. Configura el Cron: * * * * * php /ruta/artisan schedule:run >> /dev/null 2>&1`n"
