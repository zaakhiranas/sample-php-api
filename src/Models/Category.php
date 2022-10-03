<?php

namespace Models;

use PDO;

class Category extends Models
{
    public function __construct()
    {
        parent::__construct();
    }
    public function findAll(): array | false
    {
        $sql = 'SELECT * FROM categories';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    public function find(string $slug): array | false
    {
        $sql = 'SELECT * FROM categories WHERE slug=:slug';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }
    public function findId(int $id): array | false
    {
        $sql = 'SELECT * FROM categories WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
    public function create(array $data): string
    {
        $sql = 'INSERT INTO categories (title, slug, description, status) VALUES (:title, :slug, :description, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? false, PDO::PARAM_BOOL);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }
    public function update(int $id, array $data): int
    {
        $fields = [];
        if (!empty($data['title'])) {
            $fields['title'] = [$data['title'], PDO::PARAM_STR];
            $fields['slug'] = [$data['slug'], PDO::PARAM_STR];
        }
        if (!empty($data['description'])) {
            $fields['description'] = [$data['description'], PDO::PARAM_STR];
        }
        if (array_key_exists('status', $data)) {
            $fields['status'] = [$data['status'], PDO::PARAM_BOOL];
        }
        if (empty($fields)) {
            return 0;
        }
        $sets = array_map(function ($val) {
            return "$val=:$val";
        }, array_keys($fields));

        $sql = 'UPDATE categories SET ' . implode(', ', $sets) . ' WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($fields as $k => $v) {
            $stmt->bindValue(":$k", $v[0], $v[1]);
        }
        $stmt->execute();

        return $stmt->rowCount();
    }
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM categories WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return;
    }
}
