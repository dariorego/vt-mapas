#!/bin/bash
# =============================================================================
# deploy.sh — Script de Deploy para o servidor Barreto Express
# Servidor: 147.93.15.198
# Uso: ./deploy.sh
# =============================================================================

set -e

# ─── Configurações ────────────────────────────────────────────────────────────
SERVER_IP="147.93.15.198"
SERVER_USER="root"
APP_DIR="/var/www/barretosexpress"
BRANCH="main"

# ─── Cores ────────────────────────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info()    { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ─── Deploy ───────────────────────────────────────────────────────────────────
info "Iniciando deploy para $SERVER_IP..."

ssh "${SERVER_USER}@${SERVER_IP}" bash <<'ENDSSH'
set -e

APP_DIR="/var/www/barretosexpress"
BRANCH="main"

echo "==> Navegando para o diretório da aplicação..."
cd "$APP_DIR"

echo "==> Atualizando código..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Rebuilding e reiniciando containers..."
docker compose -f docker-compose.prod.yml pull --quiet
docker compose -f docker-compose.prod.yml up -d --build --remove-orphans

echo "==> Aguardando serviços ficarem saudáveis..."
sleep 10
docker compose -f docker-compose.prod.yml ps

echo "==> Limpando imagens antigas..."
docker image prune -f

echo ""
echo "✅ Deploy concluído com sucesso!"
ENDSSH

info "Deploy finalizado!"
