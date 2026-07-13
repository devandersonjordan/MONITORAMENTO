#!/bin/bash
set -e

echo "============================================"
echo "  SolarSaaS - Deploy Produção"
echo "============================================"

# Verificar se .env.production existe
if [ ! -f backend/.env.production ]; then
    echo "ERRO: backend/.env.production não encontrado!"
    echo "Copie o template e configure as credenciais:"
    echo "  cp backend/.env.production.example backend/.env.production"
    exit 1
fi

# 1. Build do frontend
echo ""
echo "[1/5] Build do frontend..."
cd frontend
npm ci
npm run build
cd ..

# 2. Subir containers
echo ""
echo "[2/5] Subindo containers..."
docker compose -f docker-compose.production.yml up -d --build

echo ""
echo "[3/5] Aguardando serviços..."
sleep 10

# 3. Migrations
echo ""
echo "[4/5] Rodando migrations..."
docker compose -f docker-compose.production.yml exec -T app php artisan migrate --force

# 4. Cache e otimizações
echo ""
echo "[5/5] Otimizando para produção..."
docker compose -f docker-compose.production.yml exec -T app php artisan config:cache
docker compose -f docker-compose.production.yml exec -T app php artisan route:cache
docker compose -f docker-compose.production.yml exec -T app php artisan view:cache

echo ""
echo "============================================"
echo "  Deploy concluído!"
echo "============================================"
echo ""
echo "  App: https://app.seudominio.com.br"
echo ""

# SSL (primeira vez)
echo "Para configurar SSL (primeira vez):"
echo "  docker compose -f docker-compose.production.yml run --rm certbot certonly --webroot --webroot-path=/var/www/certbot -d app.seudominio.com.br"
echo ""
