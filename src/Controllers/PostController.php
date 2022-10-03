<?php

namespace Controllers;

use Models\Post;
use Config\HttpResponse;
use Config\JWTHandler;
use Models\Category;

class PostController
{
    private $limit = 500;
    private $page = 0;
    private $search = [];
    public function index()
    {
        // restricte query which can search
        $sets = ['author', 'slug', 'category', 'tags', 'status'];
        if (isset($_GET['limit'])) {
            $this->limit = $_GET['limit'];
        }
        if (isset($_GET['page'])) {
            $this->page = $_GET['page'];
        }
        foreach ($_GET as $k => $v) {
            if (in_array($k, $sets)) {
                $this->search[$k] = $v;
            }
            if ($this->search['author']) {
                $this->search['username'] = $this->search['author'];
                unset($this->search['author']);
            }
        }
        $model = new Post();
        $data = $model->findAll($this->limit, $this->page, $this->search);
        HttpResponse::respondOK('posts', $data);
    }
    public function slug($slug)
    {
        $model = new Post();
        $data = $model->find($slug);
        if ($data) {
            HttpResponse::respondOK('posts', $data);
        } else {
            HttpResponse::respondNotFound();
        }
    }
    public function create()
    {
        $userdata = JWTHandler::getHeader(false);
        $postdata = (array) json_decode(file_get_contents('php://input'), true);
        $postdata['user_id'] = $userdata['sub']->id;
        $errors = $this->validateData($postdata);
        if (!empty($errors)) {
            HttpResponse::respondUnprocessEntity($errors);
        }
        $postdata = $this->sanitizeData($postdata);
        $model = new Post();
        $data = $model->create($postdata);

        HttpResponse::respondOK('msg', 'Create at row ' . $data);
    }
    public function update($id)
    {
        $userdata = JWTHandler::getHeader(false);
        $model = new Post();
        if ($userdata['sub']->role === 'admin') {
            $post = $model->findId($id);
        } else {
            $post = $model->findId($id, $userdata['sub']->id);
        }
        if (!$post) {
            HttpResponse::respondNotFound('Page not found');
        }
        $post = (array) json_decode(file_get_contents('php://input'), true);
        $errors = $this->validateData($post, false);
        if (!empty($errors)) {
            HttpResponse::respondUnprocessEntity($errors);
        }
        $post = $this->sanitizeData($post);
        HttpResponse::respondOK('message', $model->update($id, $post) . ' row updated');
    }
    public function delete($id)
    {
        $userdata = JWTHandler::getHeader(false);
        if ($userdata['sub']->role !== 'admin') {
            HttpResponse::respondUnauthorized('Unauthorized');
        }
        $model = new Post();
        $post = $model->findId($id);
        if (!$post) {
            HttpResponse::respondNotFound();
        }
        $model->delete($id);
        HttpResponse::respondDelete();
    }
    private function validateData(array $data, $new = true): array
    {
        $errors = [];
        $post = new Post();
        $category = new Category();
        $data = array_map(function ($val) {
            if (is_int($val) === false) {
                trim($val);
            }

            return $val;
        }, $data);
        if ($new && empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }
        if ($new && empty($data['content'])) {
            $errors['content'] = 'Content is required';
        }
        if ($new && !empty($data['user_id'])) {
            if (!preg_match('/\d+/', $data['user_id'])) {
                $errors['user_id'] = 'User ID invalid';
            }
        }
        if (!empty($data['title'])) {
            if (preg_match('/[^a-z0-9\s]/i', $data['title'])) {
                $errors['title'] = 'Title must alphanumeric and space';
            }
            $data['title'] = preg_replace('/\s+/', ' ', trim($data['title']));
            if ($post->countTitle($data['title'])) {
                $errors['title'] = 'Title must be unique';
            }
        }
        if (!empty($data['description'])) {
            $data['description'] = preg_replace('/\s+/', ' ', $data['description']);
            if (preg_match('/[^a-z0-9\s\,\.]/i', trim($data['description']))) {
                $errors['description'] = 'Title may alphanumeric comma, period and space';
            }
        }
        if (!empty($data['tags'])) {
            if (preg_match('/[^a-z0-9\,\-\s]/i', trim($data['tags']))) {
                $errors['tags'] = 'Title may alphanumeric separate by comma';
            }
        }
        if (!empty($data['category_id'])) {
            if (is_int($data['category_id']) === false) {
                $errors['category_id'] = 'Category ID must be integer';
            }
            if (is_int($data['category_id']) === true && !$category->findId($data['category_id'])) {
                $errors['category_id'] = 'Category not found';
            }
        }
        if (!empty($data['status'])) {
            $enum = explode(',', preg_replace('/{|}/', null, $post->select_enum_all('post_status')));
            if (!in_array(strtolower($data['status']), $enum)) {
                $errors['status'] = 'Status must be ' . implode(', ', $enum);
            }
        }

        return $errors;
    }
    private function sanitizeData(array $data): array
    {
        if (array_key_exists('title', $data)) {
            $data['title'] = preg_replace('/\s+/', ' ', trim($data['title']));
            $data['slug'] = str_replace(' ', '-', strtolower($data['title']));
        }
        if (array_key_exists('tags', $data)) {
            $data['tags'] = preg_replace(['/\s+/', '/\s/', '/\,+/', '/^,|,$/'], [' ', ', ', ',', ''], trim($data['tags']));
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = htmlspecialchars(preg_replace('/\s+/', ' ', trim($data['description'])));
        }
        if (array_key_exists('content', $data)) {
            $data['content'] = htmlspecialchars($data['content']);
        }

        return $data;
    }
}
