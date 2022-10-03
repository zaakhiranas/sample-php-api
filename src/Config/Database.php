<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private ?PDO $conn = null;
    private string $host;
    private string $port;
    private string $name;
    private string $user;
    private string $pass;
    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        $this->port = $_ENV['DB_PORT'];
        $this->name = $_ENV['DB_NAME'];
        $this->user = $_ENV['DB_USER'];
        $this->pass = $_ENV['DB_PASS'];
    }
    public function get_connect(): PDO
    {
        if ($this->conn === null) {
            $dsn = "pgsql:host=$this->host;port=$this->port;dbname=$this->name;";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];

            try {
                $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }

        return $this->conn;
    }
}
