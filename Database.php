<?php
/**
 * Classe Database
 * 
 * Gerencia a conexão com o banco de dados MySQL usando PDO
 * e fornece métodos para executar queries de forma segura.
 */

class Database
{
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }

    /**
     * Estabelece conexão com o banco de dados
     * 
     * @return PDO
     * @throws PDOException
     */
    public function connect()
    {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                debug_log("Conexão com banco de dados estabelecida com sucesso!");
            } catch (PDOException $e) {
                debug_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
                throw new Exception("Falha na conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return $this->connection;
    }

    /**
     * Executa uma query SELECT e retorna os resultados
     * 
     * @param string $query SQL query
     * @param array $params Parâmetros para prepared statement
     * @return array Resultados da query
     */
    public function query($query, $params = [])
    {
        try {
            $stmt = $this->connect()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            debug_log("Erro ao executar query: " . $e->getMessage());
            throw new Exception("Erro ao executar query: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query SELECT e retorna apenas uma linha
     * 
     * @param string $query SQL query
     * @param array $params Parâmetros para prepared statement
     * @return array|false Resultado da query ou false
     */
    public function queryOne($query, $params = [])
    {
        try {
            $stmt = $this->connect()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            debug_log("Erro ao executar query: " . $e->getMessage());
            throw new Exception("Erro ao executar query: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query de UPDATE, INSERT ou DELETE
     * 
     * @param string $query SQL query
     * @param array $params Parâmetros para prepared statement
     * @return int Número de linhas afetadas
     */
    public function execute($query, $params = [])
    {
        try {
            $stmt = $this->connect()->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            debug_log("Erro ao executar comando: " . $e->getMessage());
            throw new Exception("Erro ao executar comando: " . $e->getMessage());
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction()
    {
        return $this->connect()->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit()
    {
        return $this->connect()->commit();
    }

    /**
     * Reverte uma transação
     */
    public function rollback()
    {
        return $this->connect()->rollback();
    }

    /**
     * Retorna o ID do último registro inserido
     * 
     * @return string
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Fecha a conexão com o banco de dados
     */
    public function close()
    {
        $this->connection = null;
    }
}
