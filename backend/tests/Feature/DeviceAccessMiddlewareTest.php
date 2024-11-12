<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qtvhao\DeviceAccessControl\Middleware\DeviceAccessMiddleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

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

    public function test_learning_articles_endpoint_with_token_but_missing_device_info()
    {
        // Tạo người dùng và lấy token hợp lệ
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Gửi yêu cầu với token hợp lệ nhưng thiếu thông tin thiết bị (Device-Id và Device-Type)
        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/learning/articles');

        // Kiểm tra phản hồi có mã trạng thái 400 Bad Request
        $response->assertStatus(Response::HTTP_BAD_REQUEST);

        // Kiểm tra thông báo lỗi trong phản hồi
        $response->assertSeeText('Thiếu thông tin thiết bị');
    }

    public function test_learning_articles_endpoint_device_limit_exceeded()
    {
        // Tạo người dùng và lấy token
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Giả lập giới hạn thiết bị bị vượt quá trong DeviceAccessOrchestrator
        $orchestratorMock = $this->createMock(\Qtvhao\DeviceAccessControl\Core\UseCases\DeviceAccessOrchestrator::class);
        $orchestratorMock->method('execute')->willReturn(false); // giả lập trả về false (vượt quá giới hạn thiết bị)
        $this->app->instance(\Qtvhao\DeviceAccessControl\Core\UseCases\DeviceAccessOrchestrator::class, $orchestratorMock);

        // Gửi yêu cầu với token và giả lập vượt quá giới hạn thiết bị
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Device-Id' => 'device_123',
            'Device-Type' => 'mobile',
        ])->getJson('/learning/articles');

        // Kiểm tra phản hồi có mã trạng thái 403 Forbidden
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        // Kiểm tra thông báo lỗi trong phản hồi
        $response->assertSeeText('Vượt quá giới hạn thiết bị truy cập');
    }

    public function test_learning_articles_endpoint_exceeds_device_limit_without_mock()
    {
        // Tạo người dùng và lấy token
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Tạo mảng các Device-Id và Device-Type để lần lượt kiểm tra
        $deviceIds = ['device_1', 'device_2', 'device_3', 'device_4'];
        $deviceTypes = ['Web', 'Tablet', 'Mobile'];

        // Gửi yêu cầu với các cặp Device-Id và Device-Type khác nhau
        foreach ($deviceIds as $index => $deviceId) {
            $deviceType = $deviceTypes[$index % count($deviceTypes)]; // Xoay vòng qua các loại thiết bị

            // Gửi yêu cầu với token, Device-Id và Device-Type
            $response = $this->withHeaders([
                'Authorization' => "Bearer $token",
                'Device-Id' => $deviceId,
                'Device-Type' => $deviceType,
            ])->getJson('/learning/articles');

            // Với 3 yêu cầu đầu, mã trạng thái sẽ là 200 OK
            if ($index < 3) {
                $response->assertStatus(Response::HTTP_OK);
            } else {
                // Ở yêu cầu thứ 4, kiểm tra xem mã trạng thái là 403 Forbidden và có thông báo lỗi
                $response->assertStatus(Response::HTTP_FORBIDDEN);
                $response->assertSeeText('Vượt quá giới hạn thiết bị truy cập');
            }
        }
    }
}
