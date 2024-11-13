<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Qtvhao\DeviceAccessControl\Core\Enums\DeviceEnums;

class DeviceAccessMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set up the facade root
        \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
    }
    /**
     * Helper function to log in and retrieve a valid token.
     */
    protected function getTokenForUser(User $user, $deviceType = DeviceEnums::DEVICE_TYPE_WEB, $deviceId = 'web-device-id')
    {
        // Perform login to get the token
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_type' => $deviceType,
            'device_id' => $deviceId,
            'device_name' => 'device-name',
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['token']);

        // Extract and return the token from the response
        return $response->json('token');
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
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $token = $this->getTokenForUser($user);

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
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $token = $this->getTokenForUser($user);

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
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $token = $this->getTokenForUser($user);
        
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
            $response->assertSeeText($expectedMessage);
            $response->assertStatus($expectedStatus);
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
            (object) ['deviceId' => 'device_1', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''], // lần đầu tiên truy cập bằng thiết bị Web
            (object) ['deviceId' => 'device_2', 'deviceType' => 'Tablet', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''], // lần đầu tiên truy cập bằng thiết bị Tablet
            (object) ['deviceId' => 'device_3', 'deviceType' => 'Mobile', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''], // lần đầu tiên truy cập bằng thiết bị Mobile, không vượt quá giới hạn
            (object) ['deviceId' => 'device_4', 'deviceType' => 'Mobile', 'expectedStatus' => Response::HTTP_FORBIDDEN, 'expectedMessage' => 'Vượt quá giới hạn thiết bị truy cập'], // truy cập bằng thiết bị Mobile, vượt quá giới hạn
        ];

        $suite2 = [
            (object) ['deviceId' => 'device_5', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''], // lần đầu tiên truy cập
            (object) ['deviceId' => 'device_5', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_OK, 'expectedMessage' => ''], // truy cập lại cùng thiết bị, không vượt quá giới hạn
            (object) ['deviceId' => 'device_6', 'deviceType' => 'Web', 'expectedStatus' => Response::HTTP_FORBIDDEN, 'expectedMessage' => 'Vượt quá giới hạn thiết bị truy cập'], // truy cập với thiết bị mới, loại thiết bị giống như thiết bị trước đó, vượt quá giới hạn
        ];

        return [
            [$suite1],
            [$suite2],
        ];
    }
}
