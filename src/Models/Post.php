<?php

namespace Models;

use PDO;

class Post extends Models
{
    public function __construct()
    {
        parent::__construct();
    }
    public function findAll(?int $limit, ?int $page, ?array $data): array | false
    {
        if (!empty($data)) {
            if (array_key_exists('category', $data)) {
                $category = new Category();
                $data['category_id'] = $category->find(strtolower($data['category']))['id'];
                unset($data['category']);
            }
            foreach ($data as $k => $v) {
                if (!in_array($k, ['username'])) {
                    $data["posts.$k"] = $v;
                    unset($data[$k]);
                }
            }
            $sets = array_map(function ($value) {
                return $value . '=:' . str_replace('posts.', null, $value);
            }, array_keys($data));
            if (count($data) === 1) {
                if ($data['posts.tags']) {
                    $search = ' WHERE ' . str_replace('=', ' LIKE ', implode($sets));
                } else {
                    $search = ' WHERE ' . implode($sets);
                }
            } else {
                if ($data['posts.tags']) {
                    unset(array_flip($sets)['posts.tags=:tags']);
                    $search = ' WHERE ' . implode(' AND ', $sets);
                    $search .= ' OR posts.tags LIKE :tags';
                } else {
                    $search = ' WHERE ' . implode(' AND ', $sets);
                }
            }
        }

        $offset = $page * $limit;

        $sql = 'SELECT posts.*, username as author, categories.title as category FROM posts
        LEFT JOIN users ON posts.user_id=users.id
        LEFT JOIN categories ON posts.category_id=categories.id';

        !empty($data) ? $sql .= $search : null;

        $sql .= ' ORDER BY posts.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $this->conn->prepare($sql);

        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if ($k === 'posts.tags') {
                    $stmt->bindValue(':' . str_replace('posts.', null, $k), "%$v%", PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(':' . str_replace('posts.', null, $k), $v, PDO::PARAM_STR);
                }
            }
        }

        $stmt->execute();

        return array_map(function ($val) {
            unset($val['user_id'], $val['category_id']);
            $val['content'] = htmlspecialchars_decode($val['content']);

            return $val;
        }, $stmt->fetchAll());
    }
    public function find(string $slug): array | false
    {
        $sql = 'SELECT posts.*, username as author, categories.title as category FROM posts 
        LEFT JOIN users ON posts.user_id=users.id 
        LEFT JOIN categories ON posts.category_id=categories.id 
        WHERE posts.slug=:slug';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch();
        if ($data) {
            unset($data['user_id'], $data['category_id']);
            $data['content'] = htmlspecialchars_decode($data['content']);
        }

        return $data;
    }
    public function findId(int $id, ?int $user_id = null): array | false
    {
        $sql = 'SELECT * FROM posts WHERE id=:id';
        if ($user_id) {
            $sql .= ' AND user_id=:user_id';
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($user_id) {
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $data = $stmt->fetch();
        if ($data) {
            $data['content'] = htmlspecialchars_decode($data['content']);
        }

        return $data;
    }
    public function countTitle(string $needle): bool
    {
        $sql = 'SELECT title FROM posts';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $data = array_map(function ($value) {
            return strtolower($value['title']);
        }, $stmt->fetchAll());

        return in_array(strtolower($needle), $data);
    }
    public function create(array $data): string
    {
        $sql = 'INSERT INTO posts (title, slug, description, content, user_id, category_id, status) VALUES 
                (:title, :slug, :description, :content, :user_id, :category_id, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':category_id', $data['category_id'], empty($data['category_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }
    public function update(int $id, array $data): int
    {
        $fields = [];
        if (array_key_exists('title', $data)) {
            $fields['title'] = [$data['title'], PDO::PARAM_STR];
            $fields['slug'] = [$data['slug'], PDO::PARAM_STR];
        }
        if (array_key_exists('description', $data)) {
            $fields['description'] = [$data['description'], $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
        }
        if (array_key_exists('content', $data)) {
            $fields['content'] = [$data['content'], PDO::PARAM_STR];
        }
        if (array_key_exists('status', $data)) {
            $fields['status'] = [$data['status'], PDO::PARAM_STR];
        }
        if (array_key_exists('category_id', $data)) {
            $fields['category_id'] = [$data['category_id'], PDO::PARAM_INT];
        }
        if (array_key_exists('tags', $data)) {
            $fields['tags'] = [$data['tags'], PDO::PARAM_STR];
        }
        if (empty($fields)) {
            return 0;
        }
        $sets = array_map(function ($value) {
            return $value . '=:' . $value;
        }, array_keys($fields));
        $sql = 'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($fields as $k => $v) {
            $stmt->bindValue(":$k", $v[0], $v[1]);
        }
        $stmt->execute();

        return $stmt->rowCount();
    }
    public function delete(string $id): void
    {
        $sql = 'DELETE FROM posts WHERE id=:id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return;
    }
    public function select_enum_all(string $enum_name)
    {
        $sql = "SELECT enum_range(null::$enum_name) as $enum_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch()[$enum_name];
    }
}
