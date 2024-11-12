<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

class DeviceAccessMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set up the facade root
        \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
    }
   
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

    #[DataProvider('deviceProvider')]
    public function test_learning_articles_endpoint_device_limit_exceeded_without_mock($requests)
    {
        // Tạo người dùng và lấy token
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        foreach ($requests as $request) {
            $deviceId = $request->deviceId;
            $deviceType = $request->deviceType;
            $expectedStatus = $request->expectedStatus;
            $expectedMessage = $request->expectedMessage;

            // Gửi yêu cầu với token và thông tin thiết bị từ dataProvider
            $response = $this->withHeaders([
                'Authorization' => "Bearer $token",
                'Device-Id' => $deviceId,
                'Device-Type' => $deviceType,
            ])->getJson('/learning/articles');

            // Kiểm tra phản hồi mã trạng thái và thông báo lỗi dự kiến
            $response->assertStatus($expectedStatus);
            $response->assertSeeText($expectedMessage);
        }    
    }

    /**
     * Data provider for test_learning_articles_endpoint_device_limit_exceeded.
     *
     * @return array
     */
    public static function deviceProvider()
    {
        $suite1 = [
            (object) ['deviceId' => 'device_1', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''],                 // Lần 1: truy cập hợp lệ
            (object) ['deviceId' => 'device_2', 'deviceType' => 'Tablet', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''],                 // Lần 2: truy cập hợp lệ
            (object) ['deviceId' => 'device_3', 'deviceType' => 'Mobile', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''],                // Lần 3: truy cập hợp lệ
            (object) ['deviceId' => 'device_4', 'deviceType' => 'Mobile', 'expectedStatus' => Response::HTTP_FORBIDDEN, 'expectedMessage' => 'Vượt quá giới hạn thiết bị truy cập'], // Lần 4: vượt quá giới hạn
        ];

        $suite2 = [
            (object) ['deviceId' => 'device_1', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''],                 // Lần 1: truy cập hợp lệ
            (object) ['deviceId' => 'device_1', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''],                 // Lần 2: truy cập hợp lệ
            (object) ['deviceId' => 'device_2', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_FORBIDDEN, 'expectedMessage' => 'Vượt quá giới hạn thiết bị truy cập'], // Lần 3: vượt quá giới hạn
        ];

        return [
            [$suite1],
            [$suite1],
        ];
    }
}
