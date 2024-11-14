<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Qtvhao\DeviceAccessControl\Core\UseCases\DeviceAccessOrchestrator;
use Qtvhao\DeviceAccessControl\Core\Data\DeviceData;

class AuthController extends Controller
{
    protected $orchestrator;

    public function __construct(DeviceAccessOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    // Register new user
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    // Login user
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'device_type' => 'required|string',
            'device_uuid' => 'required|string',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $credentials = $request->only('email', 'password');
        $deviceType = $request->input('device_type'); // web, app, tablet
        $deviceUuid = $request->input('device_uuid');
        $deviceName = $request->input('device_name');

        $isAuthenticated = \Auth::attempt($credentials);
        if (!$isAuthenticated) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        $user = \Auth::user();

        // Kiểm tra quyền truy cập thiết bị
        $deviceData = new DeviceData(
            deviceUuid: $deviceUuid,
            deviceType: $deviceType,
            deviceName: $deviceName,
            userId: $user->id,
        );
        $this->orchestrator->execute($deviceData);

        // Tạo JWT với thông tin thiết bị
        $customClaims = [
            'dev' => [
                'uuid' => $deviceUuid,
                'type' => $deviceType,
            ]
        ];
    
        $token = JWTAuth::claims($customClaims)->fromUser($user);
    
        return response()->json(['token' => $token]);
    }

    // Logout user
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }
}
