<?php

namespace Models;

use PDO;

class Auth extends Models
{
    public function __construct()
    {
        parent::__construct();
    }
    public function login(array $data): array | false
    {
        if (filter_var($data['username'], FILTER_VALIDATE_EMAIL)) {
            $user = $this->findEmail($data['username']);
        } else {
            $user = $this->findUsername($data['username']);
        }
        if (!password_verify($data['password'], $user['password'])) {
            $user = false;
        }

        return $user;
    }
    public function register(array $data): string
    {
        $sql = 'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', filter_var($data['email'], FILTER_SANITIZE_EMAIL), PDO::PARAM_STR);
        $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }
    private function findUsername(string $data): array | false
    {
        $sql = 'SELECT * FROM users WHERE username=:username';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':username', $data, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }
    private function findEmail(string $data): array | false
    {
        $sql = 'SELECT * FROM users WHERE email=:email';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $data, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }
    public function countUsername(string $needle): bool
    {
        $sql = 'SELECT username FROM users';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $check = array_map(function ($value) {
            return strtolower($value['username']);
        }, $stmt->fetchAll());

        return in_array(strtolower($needle), $check);
    }
    public function findById(int $id): array | false
    {
        $sql = 'SELECT * FROM users WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
    public function countEmail(string $email): bool
    {
        $sql = 'SELECT email FROM users';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $check = array_map(function ($value) {
            return strtolower($value['email']);
        }, $stmt->fetchAll());

        return in_array(strtolower($email), $check);
    }
    public function delete_user_token(string $token): int
    {
        $sql = 'DELETE FROM user_token WHERE refresh_token=:refresh_token';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':refresh_token', $token, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount();
    }
    public function create_user_token(array $data): int
    {
        $sql = 'INSERT INTO user_token (refresh_token, expires_at) VALUES (:refresh_token, :expires_at)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':refresh_token', $data['refresh_token'], PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', $data['expires_at'], PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount();
    }
    public function get_user_token(string $token): int
    {
        $sql = 'SELECT * FROM user_token WHERE refresh_token=:refresh_token';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':refresh_token', $token, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount();
    }
    public function change_password(int $id, string $password): int
    {
        $sql = 'UPDATE users SET password=:password WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
