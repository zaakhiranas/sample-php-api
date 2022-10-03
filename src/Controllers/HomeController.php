<?php

namespace Controllers;

use Models\Auth;
use Config\HttpResponse;
use Config\JWTHandler;

class HomeController
{
    public function login()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            HttpResponse::respondUnprocessEntity('Data cannot be empty');
        }
        if (!$data) {
            HttpResponse::respondUnprocessEntity('Data cannot be empty');
        }
        $model = new Auth();
        $user = $model->login($data);
        if ($user === false) {
            HttpResponse::respondUnauthorized('Invalid credentials');
        }
        $token = JWTHandler::setHeader($user);
        HttpResponse::respondOK('token', $token);
    }
    public function register()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            HttpResponse::respondBadRequest('Data cannot be empty');
        }
        if (!empty($this->validateData($data))) {
            HttpResponse::respondUnprocessEntity($this->validateData($data));
        }
        $model = new Auth();
        $user = $model->register($data);
        HttpResponse::respondOK('row', $user);
    }
    public function refresh()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            HttpResponse::respondBadRequest('Data cannot be empty');
        }
        if (!array_key_exists('token', $data)) {
            HttpResponse::respondBadRequest('Refresh token required');
        }
        $newdata = JWTHandler::setRefresh($data['token']);
        if (!$newdata) {
            HttpResponse::respondBadRequest('Token invalid');
        }
        HttpResponse::respondOK('token', $newdata);
    }
    public function logout()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            HttpResponse::respondBadRequest('Data cannot be empty');
        }
        if (!array_key_exists('token', $data)) {
            HttpResponse::respondBadRequest('Refresh token required');
        }
        $model = new Auth();
        if (!$model->delete_user_token(hash_hmac('SHA256', $data['token'], $_ENV['REFRESH']))) {
            HttpResponse::respondBadRequest('Token invalid');
        }
        HttpResponse::respondOK('msg', 'Logout success');
    }
    public function me()
    {
        JWTHandler::getHeader();
    }
    public function update_password()
    {
        $model = new Auth();
        $user = $model->findById(JWTHandler::getHeader(false)['sub']->id);
        $userdata = (array) json_decode(file_get_contents('php://input'), true);
        $errors = $this->validatePasswordChange($userdata, $user['password']);
        if (!empty($errors)) {
            HttpResponse::respondUnprocessEntity($errors);
        }
        $savedata = $model->change_password($user['id'], $userdata['newpassword']);
        HttpResponse::respondOK('msg', $savedata);
    }
    private function validateData(array $data, bool $new = true): array
    {
        $model = new Auth();
        $errors = [];
        if ($new && empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        if ($new && empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        if ($new && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        if (!empty($data['username'])) {
            if (preg_match('/[^a-z0-9]/i', $data['username'])) {
                $errors['username'] = 'Username must alphanumeric only';
            }
            if ($model->countUsername($data['username'])) {
                $errors['username'] = 'Username must be unique';
            }
        }
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email entered invalid';
            }
            if ($model->countEmail($data['email'])) {
                $errors['email'] = 'Email must be unique';
            }
        }
        if (!empty($data['password'])) {
            if (empty($data['passwordconfirm'])) {
                $errors['passwordconfirm'] = 'Password confirm is required';
            }
            if ($data['password'] !== $data['passwordconfirm']) {
                $errors['password'] = 'Password does not match';
            }
            if (strlen($data['password']) < 6) {
                $errors['password'] = 'Password cannot less than 6 character';
            }
            if (preg_match('/\s/', $data['password'])) {
                $errors['password'] = 'Password cannot contain whitespace';
            }
        }

        return $errors;
    }
    private function validatePasswordChange(array $data, string $currentpassword): array
    {
        $errors = [];
        if (empty($data['currentpassword'])) {
            $errors['currentpassword'] = 'Current password is required';
        }
        if (empty($data['newpassword'])) {
            $errors['newpassword'] = 'New password is required';
        }
        if (empty($data['verifypassword'])) {
            $errors['verifypassword'] = 'Verify password is required';
        }
        if (!empty($data['currentpassword'])) {
            if (!password_verify($data['currentpassword'], $currentpassword)) {
                $errors['currentpassword'] = 'Current password not match';
            }
        }
        if (!empty($data['newpassword'])) {
            if ($data['newpassword'] === $data['currentpassword']) {
                $errors['newpassword'] = 'New password cannot same as current';
            }
            if (strlen($data['newpassword']) < 6) {
                $errors['newpassword'] = 'New password cannot less than 6 character';
            }
            if (preg_match('/\s/', $data['newpassword'])) {
                $errors['newpassword'] = 'New password cannot contain whitespace';
            }
        }
        if (!empty($data['verifypassword'])) {
            if ($data['verifypassword'] !== $data['newpassword']) {
                $errors['verifypassword'] = 'Verify and New password not match';
            }
        }

        return $errors;
    }
}
