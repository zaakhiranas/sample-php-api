<?php

namespace Config;

class HttpResponse
{
    public static function respondOK(string $name, array|int|string $data = null): void
    {
        http_response_code(200);
        echo json_encode([
            'status' => 200,
            'errors' => false,
            $name => $data,
        ], 128);
        exit;
    }
    public static function respondNotFound(): void
    {
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'errors' => 'Page not found',
        ]);
        exit;
    }
    public static function respondBadRequest(array|string $data = null): void
    {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'errors' => $data,
        ]);
        exit;
    }
    public static function respondUnauthorized(array|string $data): void
    {
        http_response_code(401);
        echo json_encode([
            'status' => 401,
            'errors' => $data,
        ]);
        exit;
    }
    public static function respondUnprocessEntity(array|string $data): void
    {
        http_response_code(422);
        echo json_encode([
            'status' => 422,
            'errors' => $data,
        ]);
        exit;
    }
    public static function respondDelete(): void
    {
        http_response_code(204);
        exit;
    }
}
