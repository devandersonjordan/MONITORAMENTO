Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  SolarSaaS - Setup Local (apos reboot)" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar WSL
Write-Host "[1/7] Verificando WSL2..." -ForegroundColor Yellow
$wslOk = wsl --status 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERRO: WSL2 ainda nao esta funcionando. Reinicie o PC primeiro." -ForegroundColor Red
    exit 1
}
Write-Host "  WSL2 OK" -ForegroundColor Green

# 2. Verificar Docker
Write-Host "[2/7] Verificando Docker Desktop..." -ForegroundColor Yellow
$dockerProcess = Get-Process "Docker Desktop" -ErrorAction SilentlyContinue
if (-not $dockerProcess) {
    Write-Host "  Iniciando Docker Desktop..." -ForegroundColor Yellow
    Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"
}

Write-Host "  Aguardando Docker daemon..." -ForegroundColor Yellow
$attempts = 0
do {
    $attempts++
    docker info 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) { break }
    Write-Host "  Tentativa $attempts... (pode levar 1-2 min na primeira vez)" -ForegroundColor DarkGray
    Start-Sleep -Seconds 10
} while ($attempts -lt 30)

if ($attempts -ge 30) {
    Write-Host "ERRO: Docker nao iniciou. Abra o Docker Desktop manualmente." -ForegroundColor Red
    exit 1
}
Write-Host "  Docker OK" -ForegroundColor Green

# 3. Ir para pasta do projeto
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectDir
Write-Host ""
Write-Host "[3/7] Subindo containers Docker..." -ForegroundColor Yellow
docker compose up -d --build

Write-Host ""
Write-Host "[4/7] Aguardando banco de dados..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host ""
Write-Host "[5/7] Instalando dependencias e configurando Laravel..." -ForegroundColor Yellow
docker compose exec -T app composer install --no-interaction
docker compose exec -T app php artisan key:generate --force
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --force

Write-Host ""
Write-Host "[6/7] Limpando cache..." -ForegroundColor Yellow
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear

Write-Host ""
Write-Host "[7/7] Gerando docs Swagger..." -ForegroundColor Yellow
docker compose exec -T app php artisan l5-swagger:generate 2>$null

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  Setup concluido!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Frontend:  http://localhost:3000" -ForegroundColor White
Write-Host "  API:       http://localhost:8000/api" -ForegroundColor White
Write-Host "  Swagger:   http://localhost:8000/api/documentation" -ForegroundColor White
Write-Host "  Mailpit:   http://localhost:8025" -ForegroundColor White
Write-Host ""
Write-Host "  Credenciais demo:" -ForegroundColor White
Write-Host "    Admin:      admin@solartechalagoas.com / password" -ForegroundColor White
Write-Host "    Funcionario: funcionario1@solartechalagoas.com / password" -ForegroundColor White
Write-Host "    Cliente:    cliente1@solartechalagoas.com / password" -ForegroundColor White
Write-Host ""
Write-Host "  Abrindo no navegador..." -ForegroundColor Yellow
Start-Process "http://localhost:3000"
