#!/bin/bash
set -e

echo "============================================"
echo "  SolarSaaS - Provisionamento do Servidor"
echo "============================================"
echo ""

if [ "$EUID" -ne 0 ]; then
    echo "ERRO: Execute como root (sudo ./provision.sh)"
    exit 1
fi

DEPLOY_USER="${1:-deploy}"
DOMAIN="agilizesolar.online"

echo "[1/8] Atualizando sistema..."
apt-get update -qq
apt-get upgrade -y -qq

echo "[2/8] Instalando dependencias..."
apt-get install -y -qq \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    ufw \
    fail2ban \
    unzip

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

echo "[6/8] Configurando usuario '$DEPLOY_USER'..."
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

echo "[8/8] Criando diretorio do projeto..."
PROJECT_DIR="/opt/solar-saas"
mkdir -p "$PROJECT_DIR"
chown -R $DEPLOY_USER:$DEPLOY_USER "$PROJECT_DIR"

echo ""
echo "============================================"
echo "  Provisionamento concluido!"
echo "============================================"
echo ""
echo "  Proximos passos:"
echo ""
echo "  1. Envie os arquivos do projeto:"
echo "     scp -r ./* $DEPLOY_USER@77.37.43.179:$PROJECT_DIR/"
echo ""
echo "  2. Acesse o servidor:"
echo "     ssh $DEPLOY_USER@77.37.43.179"
echo ""
echo "  3. Configure as senhas:"
echo "     cd $PROJECT_DIR"
echo "     nano backend/.env.production"
echo ""
echo "  4. Rode o deploy:"
echo "     ./deploy.sh"
echo ""
