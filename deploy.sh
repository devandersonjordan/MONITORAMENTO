#!/bin/bash
set -e

DOMAIN="agilizesolar.online"
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

echo "============================================"
echo "  SolarSaaS - Deploy Producao"
echo "  Dominio: $DOMAIN"
echo "============================================"

# Verificar .env.production
if [ ! -f backend/.env.production ]; then
    echo "ERRO: backend/.env.production nao encontrado!"
    echo "Configure as credenciais primeiro."
    exit 1
fi

# Verificar se senhas foram trocadas
if grep -q "TROCAR_POR_SENHA_FORTE_AQUI" backend/.env.production; then
    echo "ERRO: Troque as senhas padrao em backend/.env.production!"
    echo "  - DB_PASSWORD"
    echo "  - REDIS_PASSWORD"
    echo "  - MAIL_PASSWORD"
    echo "  - BACKUP_ARCHIVE_PASSWORD"
    exit 1
fi

# ------------------------------------------
# 1. Build do frontend
# ------------------------------------------
echo ""
echo "[1/7] Build do frontend..."
cd frontend
npm ci --production=false
VITE_API_URL="" npm run build
cd ..

# ------------------------------------------
# 2. Preparar diretorios
# ------------------------------------------
echo ""
echo "[2/7] Preparando diretorios..."
mkdir -p certbot/conf certbot/www
mkdir -p backend/storage/app/invoices backend/storage/app/reports
mkdir -p backend/storage/framework/sessions backend/storage/framework/views backend/storage/framework/cache
mkdir -p backend/storage/logs

# ------------------------------------------
# 3. Verificar se SSL ja existe
# ------------------------------------------
SSL_EXISTS=false
if [ -f "certbot/conf/live/$DOMAIN/fullchain.pem" ]; then
    SSL_EXISTS=true
    echo "[3/7] Certificado SSL encontrado."
else
    echo "[3/7] SSL nao encontrado. Iniciando com HTTP primeiro..."
    # Usar config inicial sem SSL
    cp nginx/production-initial.conf nginx/active.conf
fi

# ------------------------------------------
# 4. Subir containers
# ------------------------------------------
echo ""
echo "[4/7] Subindo containers..."

if [ "$SSL_EXISTS" = true ]; then
    cp nginx/production.conf nginx/active.conf
fi

# Usar active.conf no docker-compose
docker compose -f docker-compose.production.yml up -d --build

echo ""
echo "[5/7] Aguardando servicos ficarem prontos..."
sleep 15

# ------------------------------------------
# 5. Migrations e seed
# ------------------------------------------
echo ""
echo "[6/7] Rodando migrations..."
docker compose -f docker-compose.production.yml exec -T app php artisan migrate --force

# Verificar se tabela de roles tem dados
ROLES_COUNT=$(docker compose -f docker-compose.production.yml exec -T app php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null || echo "0")
if [ "$ROLES_COUNT" = "0" ] || [ -z "$ROLES_COUNT" ]; then
    echo "   Rodando seeders..."
    docker compose -f docker-compose.production.yml exec -T app php artisan db:seed --force
fi

# ------------------------------------------
# 6. Otimizacoes Laravel
# ------------------------------------------
echo ""
echo "[7/7] Otimizando para producao..."
docker compose -f docker-compose.production.yml exec -T app php artisan config:cache
docker compose -f docker-compose.production.yml exec -T app php artisan route:cache
docker compose -f docker-compose.production.yml exec -T app php artisan view:cache
docker compose -f docker-compose.production.yml exec -T app php artisan storage:link 2>/dev/null || true

# Permissoes
docker compose -f docker-compose.production.yml exec -T app chmod -R 775 storage bootstrap/cache
docker compose -f docker-compose.production.yml exec -T app chown -R www-data:www-data storage bootstrap/cache

# ------------------------------------------
# 7. SSL (se ainda nao existe)
# ------------------------------------------
if [ "$SSL_EXISTS" = false ]; then
    echo ""
    echo "============================================"
    echo "  Obtendo certificado SSL..."
    echo "============================================"
    docker compose -f docker-compose.production.yml run --rm certbot \
        certonly --webroot --webroot-path=/var/www/certbot \
        --email andersonjorda@gmail.com \
        --agree-tos --no-eff-email \
        -d "$DOMAIN" -d "www.$DOMAIN"

    if [ -f "certbot/conf/live/$DOMAIN/fullchain.pem" ]; then
        echo "   SSL obtido! Trocando para config HTTPS..."
        cp nginx/production.conf nginx/active.conf
        docker compose -f docker-compose.production.yml restart nginx
        echo "   HTTPS ativo!"
    else
        echo "   AVISO: SSL nao foi obtido. Verifique se o dominio aponta para este servidor."
        echo "   O site continua funcionando em HTTP."
    fi
fi

echo ""
echo "============================================"
echo "  Deploy concluido!"
echo "============================================"
echo ""
if [ "$SSL_EXISTS" = true ] || [ -f "certbot/conf/live/$DOMAIN/fullchain.pem" ]; then
    echo "  App: https://$DOMAIN"
else
    echo "  App: http://$DOMAIN"
fi
echo "  API: https://$DOMAIN/api"
echo ""
echo "  Credenciais de demo:"
echo "  Email: admin@solartechalagoas.com"
echo "  Senha: password"
echo ""
echo "  Logs: docker compose -f docker-compose.production.yml logs -f"
echo ""
