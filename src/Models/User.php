<?php

namespace Models;

use PDO;

class User extends Models
{
    public function __construct()
    {
        parent::__construct();
    }
    public function findAll(): array | false
    {
        $sql = 'SELECT * FROM users ORDER BY id DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return array_map(function ($value) {
            unset($value['password']);

            return $value;
        }, $stmt->fetchAll());
    }
    public function find(string $username): array | false
    {
        $sql = 'SELECT * FROM users WHERE username=:username';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user !== false) {
            unset($user['password']);
        }

        return $user;
    }
}
