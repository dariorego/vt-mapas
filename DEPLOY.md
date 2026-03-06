# Guia de Deploy Automatizado (Hostinger + GitHub Actions)

Este projeto utiliza **Docker** e **GitHub Actions** para publicar automaticamente as alterações na VPS da Hostinger sempre que um novo código for enviado (`push`) para a branch `main`.

Abaixo estão os passos que você precisa realizar **apenas uma vez** para configurar o ambiente.

---

## 🚀 1. Configurar o Servidor Hostinger (VPS via SSH)

1. Acesse seu servidor via SSH:
   ```bash
   ssh root@SEU_IP_DO_SERVIDOR
   ```
2. Certifique-se de que o Git, Docker e o plugin Docker Compose estejam instalados (na maioria dos templates VPS da Hostinger com Docker, isso já vem pronto).
3. Crie a pasta onde o projeto vai rodar. Recomendamos `/var/www/vt-mapas`:
   ```bash
   mkdir -p /var/www/vt-mapas
   cd /var/www/vt-mapas
   ```
4. Clone o repositório **pela primeira vez** (necessário para que a Action consiga fazer o `git pull` futuro):
   ```bash
   git clone https://github.com/dariorego/vt-mapas.git .
   ```
5. Crie e preencha o arquivo de variáveis de ambiente (`.env`) com os dados reais de produção:
   ```bash
   nano .env
   ```
   *Conteúdo do `.env`:*
   ```env
   DB_HOST=localhost # ou o IP do banco
   DB_NAME=prod_vt
   DB_USER=seu_usuario_banco
   DB_PASS=sua_senha_banco
   ```
   Salva o arquivo (Ctrl+O, Enter, Ctrl+X).

---

## 🔒 2. Configurar as Secrets no GitHub

Para que o robô do GitHub (Action) consiga acessar e atualizar o seu servidor Hostinger de forma segura, você precisa adicionar as credenciais dele no GitHub.

1. Vá até o seu repositório no GitHub: `https://github.com/dariorego/vt-mapas`
2. Clique na aba **Settings** (Configurações).
3. No painel esquerdo, navegue para **Secrets and variables > Actions**.
4. Clique no botão verde **New repository secret**.
5. Crie as seguintes variáveis (Secrets):

| Nome da Secret | Valor Exemplo | Descrição |
|---|---|---|
| `HOST_SERVER` | `192.168.1.1` | O Endereço IP público do seu Servidor VPS Hostinger. |
| `HOST_USER` | `root` | O nome de usuário SSH do servidor Hostinger (normalmente `root` ou `ubuntu`). |
| `HOST_DEPLOY_PATH` | `/var/www/vt-mapas` | O caminho absoluto que você criou no Passo 1 no seu servidor. |
| `HOST_SSH_PRIVATE_KEY` | `-----BEGIN OPENSSH ...`| O conteúdo da sua Chave Privada SSH que tem acesso ao servidor. **(Leia a Dica abaixo)** |

> **💡 Dica sobre a `HOST_SSH_PRIVATE_KEY`:**
> Se você acessa seu servidor com senha em vez de chave SSH, você precisa gerar um par de chaves no seu computador ou no servidor e autorizá-lo:
> 1. No seu servidor, rode: `ssh-keygen -t rsa -b 4096 -C "github-actions"` (não coloque senha quando perguntado).
> 2. Rode: `cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys`
> 3. Pegue o conteúdo da chave privada rodando: `cat ~/.ssh/id_rsa`
> 4. Copie **TODO** o conteúdo exibido e cole no valor da Secret `HOST_SSH_PRIVATE_KEY` no GitHub.

---

## ✅ 3. Testando o Deploy

1. Depois de tudo configurado, qualquer modificação que você enviar (push) para a branch `main`:
   ```bash
   git add .
   git commit -m "Testando CI/CD"
   git push origin main
   ```
2. Vá até a aba **Actions** no seu GitHub.
3. Você verá um workflow chamado `Deploy to Hostinger` rodando.
4. Quando ele ficar verde (🟢), significa que as alterações foram copiadas para o servidor e o Docker foi reiniciado automaticamente! Acesse a aplicação no IP ou domínio do seu servidor na porta 80.
