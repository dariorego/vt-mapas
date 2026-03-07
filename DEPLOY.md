# Guia de Deploy Automatizado (Hostinger + GitHub Actions)

Este projeto utiliza **Docker** e **GitHub Actions** para publicar automaticamente as alterações na VPS da Hostinger sempre que um novo código for enviado (`push`) para a branch `main`.

---

## 📋 Índice

1. [Instalar Docker (Pré-requisito)](#-1-instalar-docker-pré-requisito)
2. [Rodar Localmente com Docker](#-2-rodar-localmente-com-docker)
3. [Configurar o Servidor Hostinger (VPS)](#-3-configurar-o-servidor-hostinger-vps)
4. [Configurar Secrets no GitHub](#-4-configurar-secrets-no-github)
5. [Testando o Deploy Automático](#-5-testando-o-deploy-automático)
6. [Comandos Úteis do Docker](#-6-comandos-úteis-do-docker)
7. [Resolução de Problemas](#-7-resolução-de-problemas)

---

## 🐳 1. Instalar Docker (Pré-requisito)

### macOS

```bash
# Opção 1: Via Homebrew (recomendado)
brew install --cask docker

# Depois abra o app "Docker" pelo Launchpad ou Spotlight
# Aguarde até o ícone da baleia 🐳 aparecer na barra de menu
```

> **💡 Dica:** Após instalar, verifique se está funcionando:
> ```bash
> docker --version
> docker compose version
> ```

### Ubuntu / Debian (Servidor VPS)

```bash
# 1. Remover versões antigas (se houver)
sudo apt-get remove docker docker-engine docker.io containerd runc 2>/dev/null

# 2. Atualizar pacotes e instalar dependências
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg

# 3. Adicionar a chave GPG oficial do Docker
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

# 4. Adicionar o repositório do Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# 5. Instalar Docker Engine + Docker Compose
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# 6. Verificar instalação
docker --version
docker compose version
```

> **💡 Para Debian**, troque `ubuntu` por `debian` nos comandos acima.

### Windows

1. Baixe o **Docker Desktop** em: https://www.docker.com/products/docker-desktop
2. Execute o instalador e siga as instruções.
3. Reinicie o computador se solicitado.
4. Abra o Docker Desktop e aguarde ele iniciar.
5. Abra o PowerShell e verifique:
   ```powershell
   docker --version
   docker compose version
   ```

---

## 🖥️ 2. Rodar Localmente com Docker

### Estrutura dos Arquivos Docker

O projeto já possui os arquivos de Docker configurados:

| Arquivo | Descrição |
|---------|-----------|
| `Dockerfile` | Define a imagem PHP 8.2 + Apache com extensão PDO MySQL |
| `docker-compose.yml` | Orquestra os containers (app + banco de dados) |
| `.env` | Variáveis de ambiente (credenciais do banco) |

### Passo a Passo

**1. Clone o repositório (se ainda não fez):**
```bash
git clone https://github.com/dariorego/vt-mapas.git
cd vt-mapas
```

**2. Crie o arquivo `.env` a partir do exemplo:**
```bash
cp .env.example .env
```

**3. Edite o `.env` com suas credenciais:**
```env
DB_HOST=db
DB_NAME=vt_mapas
DB_USER=vt_user
DB_PASS=vt_senha_segura
```

> ⚠️ **Importante:** Quando usar Docker Compose com o banco local, o `DB_HOST` deve ser o **nome do serviço** definido no `docker-compose.yml` (ex: `db`), e **não** `localhost`.

**4. Atualize o `docker-compose.yml` para incluir o MySQL:**

O `docker-compose.yml` atual só tem o serviço `app`. Para rodar **localmente com banco de dados incluso**, use esta versão completa:

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: vt-mapas-app
    restart: always
    ports:
      - "80:80"
    env_file:
      - .env
    volumes:
      - ./data:/var/www/html/data
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    container_name: vt-mapas-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root_password_segura
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  mysql_data:
```

**5. Suba os containers:**
```bash
docker compose up -d --build
```

**6. Verifique se está rodando:**
```bash
docker compose ps
```

Saída esperada:
```
NAME             STATUS          PORTS
vt-mapas-app     Up (healthy)    0.0.0.0:80->80/tcp
vt-mapas-db      Up (healthy)    0.0.0.0:3306->3306/tcp
```

**7. Acesse a aplicação:**
Abra o navegador em: **http://localhost**

---

## 🚀 3. Configurar o Servidor Hostinger (VPS)

1. Acesse seu servidor via SSH:
   ```bash
   ssh root@SEU_IP_DO_SERVIDOR
   ```

2. Instale o Docker seguindo a [seção de instalação para Ubuntu/Debian](#ubuntu--debian-servidor-vps) acima (se ainda não estiver instalado).

3. Crie a pasta do projeto:
   ```bash
   mkdir -p /var/www/vt-mapas
   cd /var/www/vt-mapas
   ```

4. Clone o repositório pela **primeira vez**:
   ```bash
   git clone https://github.com/dariorego/vt-mapas.git .
   ```

5. Crie o arquivo `.env` com os dados de **produção**:
   ```bash
   nano .env
   ```
   ```env
   DB_HOST=db             # Se usar o MySQL no Docker
   DB_NAME=prod_vt
   DB_USER=seu_usuario_banco
   DB_PASS=sua_senha_banco
   ```
   Salve: `Ctrl+O` → `Enter` → `Ctrl+X`

6. Suba a aplicação:
   ```bash
   docker compose up -d --build
   ```

> **💡 Banco de dados externo (Hostinger MySQL):** Se o seu banco MySQL já roda fora do Docker (ex: serviço MySQL do painel Hostinger), use o IP/host real do banco no `DB_HOST` e **não** inclua o serviço `db` no `docker-compose.yml`. Mantenha apenas o serviço `app`.

---

## 🔒 4. Configurar Secrets no GitHub

Para que o GitHub Actions consiga acessar e atualizar o servidor automaticamente:

1. Acesse: `https://github.com/dariorego/vt-mapas` → **Settings** → **Secrets and variables** → **Actions**
2. Clique em **New repository secret** e crie:

| Nome da Secret | Exemplo | Descrição |
|---|---|---|
| `HOST_SERVER` | `192.168.1.1` | IP público do servidor VPS |
| `HOST_USER` | `root` | Usuário SSH (normalmente `root`) |
| `HOST_DEPLOY_PATH` | `/var/www/vt-mapas` | Caminho absoluto do projeto no servidor |
| `HOST_SSH_PRIVATE_KEY` | `-----BEGIN OPENSSH...` | Chave privada SSH (veja dica abaixo) |

> **💡 Gerando a Chave SSH:**
> ```bash
> # No servidor, gere o par de chaves:
> ssh-keygen -t rsa -b 4096 -C "github-actions"
> # (não coloque senha quando perguntado)
>
> # Autorize a chave:
> cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
>
> # Copie a chave PRIVADA para colar no GitHub:
> cat ~/.ssh/id_rsa
> ```
> Cole **todo** o conteúdo exibido no valor da Secret `HOST_SSH_PRIVATE_KEY`.

---

## ✅ 5. Testando o Deploy Automático

1. Faça qualquer modificação e envie para a `main`:
   ```bash
   git add .
   git commit -m "Testando CI/CD"
   git push origin main
   ```

2. Acompanhe na aba **Actions** do GitHub: `https://github.com/dariorego/vt-mapas/actions`

3. Quando o ícone ficar 🟢, acesse a aplicação no IP/domínio do servidor na **porta 80**.

### O que o workflow faz automaticamente:
```
Push na main → GitHub Actions conecta via SSH → git pull → docker compose down → docker compose up -d --build → Limpeza de imagens antigas
```

---

## 🔧 6. Comandos Úteis do Docker

### Gerenciamento de Containers

```bash
# Ver containers rodando
docker compose ps

# Ver logs em tempo real
docker compose logs -f

# Ver logs apenas da aplicação
docker compose logs -f app

# Reiniciar containers
docker compose restart

# Parar containers (sem remover)
docker compose stop

# Parar e remover containers
docker compose down

# Rebuild e subir novamente
docker compose up -d --build
```

### Debug e Inspeção

```bash
# Entrar no container da aplicação (terminal interativo)
docker exec -it vt-mapas-app bash

# Entrar no container do MySQL
docker exec -it vt-mapas-db mysql -u vt_user -p

# Ver uso de disco dos containers
docker system df

# Limpar tudo não utilizado (containers, imagens, volumes parados)
docker system prune -a
```

### Banco de Dados

```bash
# Backup do banco (se usando MySQL no Docker)
docker exec vt-mapas-db mysqldump -u root -proot_password_segura prod_vt > backup_$(date +%Y%m%d).sql

# Restaurar backup
docker exec -i vt-mapas-db mysql -u root -proot_password_segura prod_vt < backup.sql
```

---

## ❓ 7. Resolução de Problemas

### Container não sobe

```bash
# Verificar logs de erro
docker compose logs --tail=50

# Verificar se a porta 80 já está em uso
sudo lsof -i :80
```

### Erro de conexão com o banco

1. Verifique se o `DB_HOST` no `.env` está correto:
   - Usando MySQL **no Docker**: `DB_HOST=db`
   - Usando MySQL **externo**: `DB_HOST=ip_ou_host_real`

2. Verifique se o container do banco está saudável:
   ```bash
   docker compose ps
   ```

### Permissões de arquivo

```bash
# Dentro do container, corrigir permissões
docker exec -it vt-mapas-app chown -R www-data:www-data /var/www/html
```

### Espaço em disco cheio no servidor

```bash
# Limpar imagens e containers antigos
docker system prune -a -f

# Verificar espaço
df -h
```

---

## 📐 Arquitetura do Deploy

```
┌──────────────┐       push main       ┌──────────────────┐
│  Desenvolvedor│ ─────────────────────▶│   GitHub Actions  │
│  (Local)      │                       │   (deploy.yml)    │
└──────────────┘                       └────────┬─────────┘
                                                │ SSH
                                                ▼
                                       ┌──────────────────┐
                                       │  Servidor VPS     │
                                       │  (Hostinger)      │
                                       │                   │
                                       │  ┌─────────────┐  │
                                       │  │ Docker       │  │
                                       │  │  ┌────────┐  │  │
                                       │  │  │ PHP+   │  │  │
                                       │  │  │ Apache │  │  │
                                       │  │  │ :80    │  │  │
                                       │  │  └────────┘  │  │
                                       │  │  ┌────────┐  │  │
                                       │  │  │ MySQL  │  │  │
                                       │  │  │ :3306  │  │  │
                                       │  │  └────────┘  │  │
                                       │  └─────────────┘  │
                                       └──────────────────┘
```
