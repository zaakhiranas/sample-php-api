<?php

namespace Controllers;

use Config\HttpResponse;
use Models\Category;
use Config\JWTHandler;

class CategoryController
{
    private Category $model;
    public function __construct(Category $model = new Category())
    {
        $this->model = $model;
    }
    public function index()
    {
        $categories = $this->model->findAll();
        HttpResponse::respondOK('categories', $categories);
    }
    public function slug($slug)
    {
        $categories = $this->model->find($slug);
        if ($categories === false) {
            HttpResponse::respondNotFound();
        }
        HttpResponse::respondOK('categories', $categories);
    }
    public function create()
    {
        if ('admin' !== JWTHandler::getHeader(false)['sub']->role) {
            HttpResponse::respondUnauthorized('Unauthorized');
        }
        $data = (array) json_decode(file_get_contents('php://input'), true);
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            HttpResponse::respondUnprocessEntity($errors);
        }
        $data = $this->sanitizeData($data);
        HttpResponse::respondOK('msg', 'Create at row ' . $this->model->create($data));
    }
    public function update(string $id)
    {
        if ('admin' !== JWTHandler::getHeader(false)['sub']->role) {
            HttpResponse::respondUnauthorized('Unauthorized');
        }
        $category = $this->model->findId($id);
        if (!$category) {
            HttpResponse::respondNotFound();
        }
        $data = (array) json_decode(file_get_contents('php://input'));
        if (empty($data)) {
            HttpResponse::respondOK('msg', 0);
        }
        $errors = $this->validateData($data, false);
        if (!empty($errors)) {
            HttpResponse::respondUnprocessEntity($errors);
        }
        $data = $this->sanitizeData($data);
        $this->model->update($id, $data);
        HttpResponse::respondOK('msg', $this->model->update($id, $data));
    }
    public function delete(string $id)
    {
        $auth = JWTHandler::getHeader(false);
        if ('admin' !== $auth['sub']->role) {
            HttpResponse::respondUnauthorized('Unauthorized');
        }
        if (!$this->model->findId($id)) {
            HttpResponse::respondNotFound();
        }
        $this->model->delete($id);
        HttpResponse::respondDelete();
    }
    private function validateData(array $data, $new = true)
    {
        $errors = [];
        foreach ($data as $k => $v) {
            if ($k != 'status') {
                $data[$k] = preg_replace('/\s+/', ' ', trim($v));
            }
        }
        if ($new && empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }
        if (!empty($data['title'])) {
            if (preg_match('/[^a-z0-9\s]/i', $data['title'])) {
                $errors['title'] = 'Title must alphanumeric space';
            }
            if ($this->model->find(str_replace(' ', '-', strtolower($data['title'])))) {
                $errors['title'] = 'Title must be unique';
            }
        }
        if (!empty($data['description'])) {
            if (preg_match('/[^a-z0-9\s\,\.]/i', $data['description'])) {
                $errors['description'] = 'Description may alphanumeric comma, period and space';
            }
        }
        if (array_key_exists('status', $data)) {
            if (is_bool($data['status']) === false) {
                $errors['status'] = 'Must be boolean';
            }
        }

        return $errors;
    }
    private function sanitizeData(array $data)
    {
        // $t1 = microtime(true) * 1000;
        if ($data['title']) {
            $data['title'] = preg_replace('/\s+/', ' ', trim($data['title']));
            $data['slug'] = str_replace(' ', '-', strtolower($data['title']));
        }
        if (!empty($data['description'])) {
            $data['description'] = preg_replace('/\s+/', ' ', trim($data['description']));
        }
        //////////////////////////////////////////
        // $stat = array_map(function ($val) {
        //     if (!is_bool($val)) {
        //         return preg_replace('/\s+/', ' ', trim($val));
        //     }
        // }, $data);
        // $stat['status'] = $data['status'];
        ///////////////////////////////////////////////

        // foreach ($data as $k => $v) {
        //     if ($k !== 'status') {
        //         $data[$k] = preg_replace('/\s+/', ' ', trim($v));
        //     }
        //     if ($k === 'title') {
        //         $data['slug'] = str_replace(' ', '-', strtolower($v));
        //     }
        // }
        // $t2 = microtime(true) * 1000;
        // $res = ['time' => round($t2 - $t1, 4), 'data' => $data];
        // var_dump($res);
        // die;

        return $data;
    }
}
