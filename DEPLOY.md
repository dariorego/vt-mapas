# Guia de Deploy — Barreto Express

> Aplicação PHP 8.2 + Apache + MySQL 8.0, orquestrada com Docker e Traefik (SSL automático via Let's Encrypt).

---

## 🌐 Informações de Produção

| Serviço | URL | Status |
|---------|-----|--------|
| **Aplicação** | https://barretosexpress.logapp.com.br | ✅ Online |
| **Banco de Dados** | barretosexpressdb.logapp.com.br:3306 | ✅ Online |
| **Servidor** | 147.93.15.198 (Ubuntu 24.04) | ✅ Online |

---

## 📁 Estrutura no Servidor

```
/var/www/barretosexpress/
├── docker-compose.prod.yml   # Configuração dos containers de produção
├── .env.prod                 # Variáveis de ambiente (NÃO vai para o git)
├── .env                      # Cópia do .env.prod (lido pelo docker compose)
├── Dockerfile                # Imagem PHP 8.2 + Apache
├── data/                     # Volume persistente (uploads, etc.)
└── ...                       # Código da aplicação
```

---

## 🚀 Deploy de Atualização

Para enviar atualizações de código para o servidor:

```bash
# 1. Local: commitar e enviar para o git
git add .
git commit -m "sua mensagem"
git push origin main

# 2. No servidor (via SSH) ou use o script:
./deploy.sh
```

Ou acesse o servidor manualmente:
```bash
ssh root@147.93.15.198
cd /var/www/barretosexpress
git pull origin main
docker compose -f docker-compose.prod.yml up -d --build
```

---

## 🔑 Credenciais do Banco de Dados

> ⚠️ Armazenadas **apenas** no servidor em `/var/www/barretosexpress/.env.prod`

| Campo | Valor |
|-------|-------|
| Host | `barretosexpress-db` (interno) / `barretosexpressdb.logapp.com.br` (externo) |
| Banco | `prod_be` |
| Usuário | `barretosexpress` |
| Senha | Ver `.env.prod` no servidor |

---

## 🐳 Comandos Docker (no servidor)

```bash
cd /var/www/barretosexpress

# Ver status
docker compose -f docker-compose.prod.yml ps

# Ver logs em tempo real
docker compose -f docker-compose.prod.yml logs -f

# Ver logs só da app
docker compose -f docker-compose.prod.yml logs -f app

# Reiniciar serviços
docker compose -f docker-compose.prod.yml restart

# Rebuild completo
docker compose -f docker-compose.prod.yml up -d --build

# Parar tudo
docker compose -f docker-compose.prod.yml down

# Entrar no container da app
docker exec -it barretosexpress-app bash

# Entrar no MySQL
docker exec -it barretosexpress-db mysql -u barretosexpress -p prod_be
```

---

## 🔄 Setup Inicial (já executado)

O servidor já foi configurado. Para um novo servidor do zero:

```bash
# 1. Clonar o repo
git clone https://github.com/dariorego/vt-mapas.git /var/www/barretosexpress
cd /var/www/barretosexpress

# 2. Criar .env
cp .env.prod.example .env.prod  # ou criar manualmente
cp .env.prod .env

# 3. Subir containers
docker compose -f docker-compose.prod.yml up -d --build
```

---

## 🏗️ Arquitetura

```
Internet
   │
   ▼ :80/:443
┌──────────────────────────────────────────────────────┐
│  Traefik (reverse proxy + SSL Let's Encrypt)         │
│  Rede: traefik-public                                │
└──────────┬───────────────────────────────────────────┘
           │ barretosexpress.logapp.com.br
           ▼
┌──────────────────────────────────────────────────────┐
│  barretosexpress-app (PHP 8.2 + Apache)              │
│  Rede: traefik-public + backend_internal             │
└──────────┬───────────────────────────────────────────┘
           │ DB_HOST=db (rede interna)
           ▼
┌──────────────────────────────────────────────────────┐
│  barretosexpress-db (MySQL 8.0)                      │
│  Volume: barretosexpress_mysql (persistente)         │
│  Rede: backend_internal (isolada)                    │
└──────────────────────────────────────────────────────┘
```

---

## ❓ Troubleshooting

### App inacessível
```bash
# Verificar se containers estão rodando
docker compose -f docker-compose.prod.yml ps

# Ver logs de erro
docker compose -f docker-compose.prod.yml logs --tail=50 app
```

### Erro de banco de dados
```bash
# Testar conexão
docker exec barretosexpress-db mysqladmin ping -u root -p

# Ver logs do MySQL
docker compose -f docker-compose.prod.yml logs --tail=30 db
```

### SSL não funciona
```bash
# Ver logs do Traefik
docker logs traefik 2>&1 | grep barretosexpress | tail -20
```
