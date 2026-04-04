#!/bin/bash
# =============================================================================
# server-setup.sh — Setup inicial do servidor para Barreto Express
# Execute este script NO SERVIDOR via SSH ou cole manualmente.
#
# Pré-requisito: acesso root ao servidor 147.93.15.198
# =============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'
info() { echo -e "${GREEN}[✓]${NC} $1"; }
step() { echo -e "\n${YELLOW}[→]${NC} $1"; }

# ─── 1. Atualizar sistema ─────────────────────────────────────────────────────
step "Atualizando sistema..."
apt-get update -y && apt-get upgrade -y

# ─── 2. Instalar Docker ───────────────────────────────────────────────────────
step "Instalando Docker..."
if ! command -v docker &>/dev/null; then
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
    info "Docker instalado"
else
    info "Docker já instalado: $(docker --version)"
fi

# ─── 3. Instalar Git ──────────────────────────────────────────────────────────
step "Instalando Git..."
apt-get install -y git curl
info "Git: $(git --version)"

# ─── 4. Criar estrutura de diretórios ────────────────────────────────────────
step "Criando diretório da aplicação..."
mkdir -p /var/www/barretosexpress
cd /var/www/barretosexpress

# ─── 5. Clonar repositório ───────────────────────────────────────────────────
step "Clonando repositório..."
if [ ! -d ".git" ]; then
    git clone https://github.com/dariorego/vt-mapas.git .
    info "Repositório clonado"
else
    git pull origin main
    info "Repositório atualizado"
fi

# ─── 6. Criar .env.prod ───────────────────────────────────────────────────────
step "Configurando variáveis de ambiente..."
if [ ! -f ".env.prod" ]; then
    cat > .env.prod <<'EOF'
# ─── Banco de Dados ─────────────────────────────────────────────────
DB_HOST=db
DB_NAME=prod_be
DB_USER=barretosexpress
DB_PASS=TROQUE_ESTA_SENHA_AQUI

MYSQL_ROOT_PASSWORD=TROQUE_ESTA_SENHA_ROOT_AQUI

# ─── Aplicação ───────────────────────────────────────────────────────
APP_ENV=production
APP_URL=https://barretosexpress.logapp.com.br
EOF
    echo ""
    echo "⚠️  ATENÇÃO: Edite o arquivo .env.prod com as senhas reais antes de continuar!"
    echo "    nano /var/www/barretosexpress/.env.prod"
    echo ""
    echo "    Pressione ENTER para continuar após editar..."
    read -r
fi

# ─── 7. Criar rede Docker (caso não exista) ──────────────────────────────────
step "Criando rede Docker traefik-public..."
docker network create traefik-public 2>/dev/null || info "Rede já existe"

# ─── 8. Criar pasta de dados ─────────────────────────────────────────────────
step "Criando pasta de dados da aplicação..."
mkdir -p /var/www/barretosexpress/data
chown -R 33:33 /var/www/barretosexpress/data  # www-data uid

# ─── 9. Configurar firewall ──────────────────────────────────────────────────
step "Configurando firewall (UFW)..."
if command -v ufw &>/dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
    info "UFW configurado"
fi

# ─── 10. Subir os serviços ───────────────────────────────────────────────────
step "Iniciando containers Docker..."
cd /var/www/barretosexpress
docker compose -f docker-compose.prod.yml up -d --build

step "Verificando status dos serviços..."
sleep 15
docker compose -f docker-compose.prod.yml ps

echo ""
echo "============================================================"
echo "  ✅ Setup concluído!"
echo ""
echo "  📦 Serviços:"
echo "     App:   https://barretosexpress.logapp.com.br"
echo "     MySQL: barretosexpressdb.logapp.com.br:3306"
echo ""
echo "  📋 Comandos úteis:"
echo "     Ver logs:    docker compose -f docker-compose.prod.yml logs -f"
echo "     Status:      docker compose -f docker-compose.prod.yml ps"
echo "     Reiniciar:   docker compose -f docker-compose.prod.yml restart"
echo "============================================================"
