# Sistema de Otimização de Rotas de Entrega

Este sistema em PHP calcula a melhor rota de entrega para motoristas, partindo de um centro de distribuição fixo (Cliente ID 120).

## Funcionalidades

- Conexão segura com banco de dados MySQL
- Cálculo de distâncias geográficas (Fórmula de Haversine)
- Algoritmo de otimização de rota (Nearest Neighbor)
- Interface web amigável para gestão
- Script CLI para automação
- Atualização automática da ordem de entrega no banco de dados

## Instalação

1. Clone o repositório
2. Configure as credenciais do banco de dados em `config.php`
3. Execute via servidor web ou linha de comando

## Uso

### Via Servidor Local (Recomendado para Testes)
Você pode usar o servidor embutido do PHP para rodar a aplicação sem configurar Apache/Nginx.

1. Abra o terminal na pasta do projeto.
2. Execute o comando:
   ```bash
   php -S localhost:8000
   ```
3. Abra seu navegador em: `http://localhost:8000`

### Via Linha de Comando (CLI)
```bash
php optimize_routes.php [VIAGEM_ID]
```
Exemplo:
```bash
php optimize_routes.php 2025-12-22 2
```

## Estrutura de Arquivos

- `config.php`: Configurações globais e de banco de dados
- `Database.php`: Classe de abstração do banco de dados (PDO)
- `RouteOptimizer.php`: Lógica de cálculo e otimização de rotas
- `optimize_routes.php`: Script principal de execução
- `index.php`: Interface web para o usuário
