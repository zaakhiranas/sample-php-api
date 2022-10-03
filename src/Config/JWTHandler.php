<?php

namespace Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;
use Models\Auth;

class JWTHandler
{
    public static function getHeader($fetch = true)
    {
        if (!preg_match('/^Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            HttpResponse::respondBadRequest('Token required');
        }

        $token = $matches[1];

        $decode = new JWTHandler();
        $data = $decode->decode($token, strtoupper('access'));
        if ($fetch) {
            HttpResponse::respondOK('data', $data);
        } else {
            return $data;
        }
    }
    public static function setHeader(array $data): array
    {
        $token = new JWTHandler();

        $access = $token->accessPayload($data);
        $refresh = $token->refreshPayload($data['id']);

        return ['access' => $access, 'refresh' => $refresh];
    }
    public static function setRefresh(string $refresh): array| bool
    {
        $token = new JWTHandler();
        $id = $token->decode($refresh, strtoupper('refresh'));
        $refresh = hash_hmac('sha256', $refresh, $_ENV['REFRESH']);
        $model = new Auth();
        if (!$model->get_user_token($refresh)) {
            return 0;
        }
        $model->delete_user_token($refresh);
        $data = $model->findById($id['sub']->id);

        $access = $token->accessPayload($data);
        $refresh = $token->refreshPayload($data['id']);

        return ['access' => $access, 'refresh' => $refresh];
    }
    private function accessPayload(array $data): string
    {
        $payload = [
            'iss' => 'sirap.xyz',
            // 'exp' => time() + 86400,
            'exp' => time() + 5,
            'sub' => [
                'id' => $data['id'],
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $data['role'],
            ],
        ];

        return JWT::encode($payload, $_ENV['ACCESS'], 'HS256');
    }
    private function refreshPayload(int $data): string
    {
        $time = time() + 86400;
        $payload = [
            'iss' => 'sirap.xyz',
            'exp' => $time,
            'sub' => [
                'id' => $data,
            ],
        ];
        $encode = JWT::encode($payload, $_ENV['REFRESH'], 'HS256');
        $savedata = [
            'refresh_token' => hash_hmac('SHA256', $encode, $_ENV['REFRESH']),
            'expires_at' => $time,
        ];

        $model = new Auth();
        $model->create_user_token($savedata);

        return $encode;
    }
    private function decode(string $token, string $keyname): array
    {
        try {
            $decode = (array) JWT::decode($token, new Key($_ENV[$keyname], 'HS256'));
        } catch (ExpiredException $e) {
            HttpResponse::respondBadRequest($e->getMessage());
        } catch (SignatureInvalidException $e) {
            HttpResponse::respondBadRequest($e->getMessage());
        } catch (Exception $e) {
            HttpResponse::respondBadRequest($e->getMessage());
        }

        return $decode;
    }
}
