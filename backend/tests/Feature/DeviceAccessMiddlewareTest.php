<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qtvhao\DeviceAccessControl\Middleware\DeviceAccessMiddleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceAccessMiddlewareTest extends TestCase
{
   
    /**
     * Test /learning/articles endpoint without token
     */
    public function test_learning_articles_endpoint_without_token()
    {
        // Gửi yêu cầu GET đến endpoint /learning/articles mà không có token
        $response = $this->getJson('/learning/articles');

        // Kiểm tra phản hồi có mã trạng thái 400 Bad Request
        $response->assertStatus(Response::HTTP_BAD_REQUEST);

        // Kiểm tra thông báo lỗi trong phản hồi
        $response->assertSeeText('The token could not be parsed from the request');
    }


    /**
     * Test /learning/articles endpoint with invalid token
     */
    public function test_learning_articles_endpoint_with_invalid_token()
    {
        $payload = [
            'sub' => 0,
        ];
        $invalid_token = JWTAuth::factory()->customClaims($payload)->make();
        // Thêm một token không hợp lệ vào header
        $response = $this->withHeader('Authorization', 'Bearer ' . JWTAuth::encode($invalid_token))
                         ->getJson('/learning/articles');

        // Kiểm tra phản hồi có mã trạng thái 401 Unauthorized
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        // Kiểm tra thông báo lỗi trong phản hồi
        $response->assertSeeText('Không có quyền truy cập');
    }
}
