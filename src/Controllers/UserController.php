<?php

namespace Controllers;

use Config\HttpResponse;
use Models\User;

class UserController
{
    public function index()
    {
        $model = new User();
        $users = $model->findAll();
        HttpResponse::respondOK('users', $users);
    }
    public function username(string $username)
    {
        $model = new User();
        $user = $model->find($username);
        if ($user === false) {
            HttpResponse::respondNotFound();
        }
        HttpResponse::respondOK('user', $user);
    }
}
