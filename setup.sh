#!/bin/bash
set -e

echo "============================================"
echo "  SolarSaaS - Setup Completo"
echo "============================================"

# 1. Subir containers
echo ""
echo "[1/6] Subindo containers Docker..."
docker compose up -d --build

echo ""
echo "[2/6] Aguardando banco de dados..."
sleep 5

# 2. Instalar dependências PHP (se necessário)
echo ""
echo "[3/6] Instalando dependências do backend..."
docker compose exec app composer install --no-interaction

# 3. Gerar chave e rodar migrations
echo ""
echo "[4/6] Configurando Laravel..."
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force

# 4. Limpar cache
echo ""
echo "[5/6] Limpando cache..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# 5. Gerar documentação Swagger
echo ""
echo "[6/6] Gerando documentação API..."
docker compose exec app php artisan l5-swagger:generate 2>/dev/null || echo "Swagger será gerado no primeiro acesso"

echo ""
echo "============================================"
echo "  Setup concluído!"
echo "============================================"
echo ""
echo "  Frontend:  http://localhost:3000"
echo "  API:       http://localhost:8000/api"
echo "  Swagger:   http://localhost:8000/api/documentation"
echo "  Mailpit:   http://localhost:8025"
echo ""
echo "  Credenciais demo:"
echo "    Admin:    admin@solartechalagoas.com / password"
echo "    Employee: funcionario1@solartechalagoas.com / password"
echo "    Client:   cliente1@solartechalagoas.com / password"
echo ""
echo "  Para rodar testes:"
echo "    docker compose exec app php artisan test"
echo ""
