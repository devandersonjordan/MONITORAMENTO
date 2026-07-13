#!/bin/bash
set -e

echo "============================================"
echo "  SolarSaaS - Provisionamento do Servidor"
echo "============================================"
echo ""
echo "  Este script prepara um servidor Ubuntu 22.04+"
echo "  com Docker, Node.js e configura o projeto."
echo ""

if [ "$EUID" -ne 0 ]; then
    echo "ERRO: Execute como root (sudo ./provision.sh)"
    exit 1
fi

DEPLOY_USER="${1:-deploy}"
DOMAIN="${2:-app.seudominio.com.br}"

echo "[1/8] Atualizando sistema..."
apt-get update -qq
apt-get upgrade -y -qq

echo "[2/8] Instalando dependências..."
apt-get install -y -qq \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    ufw \
    fail2ban

echo "[3/8] Instalando Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
fi

echo "[4/8] Instalando Docker Compose plugin..."
apt-get install -y -qq docker-compose-plugin 2>/dev/null || true

echo "[5/8] Instalando Node.js 22..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
fi

echo "[6/8] Configurando usuário '$DEPLOY_USER'..."
if ! id "$DEPLOY_USER" &>/dev/null; then
    useradd -m -s /bin/bash "$DEPLOY_USER"
    usermod -aG docker "$DEPLOY_USER"
    mkdir -p /home/$DEPLOY_USER/.ssh
    cp /root/.ssh/authorized_keys /home/$DEPLOY_USER/.ssh/ 2>/dev/null || true
    chown -R $DEPLOY_USER:$DEPLOY_USER /home/$DEPLOY_USER/.ssh
    chmod 700 /home/$DEPLOY_USER/.ssh
    chmod 600 /home/$DEPLOY_USER/.ssh/authorized_keys 2>/dev/null || true
    echo "$DEPLOY_USER ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/$DEPLOY_USER
fi

echo "[7/8] Configurando firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "[8/8] Clonando projeto..."
PROJECT_DIR="/opt/solar-saas"
if [ ! -d "$PROJECT_DIR" ]; then
    git clone https://github.com/devandersonjordan/MONITORAMENTO.git "$PROJECT_DIR"
    chown -R $DEPLOY_USER:$DEPLOY_USER "$PROJECT_DIR"
fi

echo ""
echo "============================================"
echo "  Provisionamento concluído!"
echo "============================================"
echo ""
echo "  Próximos passos:"
echo ""
echo "  1. Configure as credenciais:"
echo "     cd $PROJECT_DIR"
echo "     cp backend/.env.example backend/.env.production"
echo "     nano backend/.env.production"
echo ""
echo "  2. Atualize o domínio no nginx:"
echo "     sed -i 's/app.seudominio.com.br/$DOMAIN/g' nginx/production.conf"
echo ""
echo "  3. Rode o deploy:"
echo "     su - $DEPLOY_USER"
echo "     cd $PROJECT_DIR"
echo "     ./deploy.sh"
echo ""
echo "  4. Configure SSL:"
echo "     docker compose -f docker-compose.production.yml run --rm certbot \\"
echo "       certonly --webroot --webroot-path=/var/www/certbot -d $DOMAIN"
echo ""
echo "  5. Configure o cron de renovação SSL:"
echo "     echo '0 3 * * * cd $PROJECT_DIR && docker compose -f docker-compose.production.yml run --rm certbot renew' | crontab -"
echo ""
