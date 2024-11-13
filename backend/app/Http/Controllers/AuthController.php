<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Qtvhao\DeviceAccessControl\Core\UseCases\AddNewDeviceUseCase;
use Qtvhao\DeviceAccessControl\Core\Data\DeviceData;

class AuthController extends Controller
{
    private $addNewDeviceUseCase;
    public function __construct(AddNewDeviceUseCase $addNewDeviceUseCase)
    {
        $this->addNewDeviceUseCase = $addNewDeviceUseCase;
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
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'device_type' => 'required|string',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');
        $deviceType = $request->input('device_type'); // web, app, tablet
        $deviceId = $request->input('device_id');
        $deviceName = $request->input('device_name');

        $isAuthenticated = \Auth::attempt($credentials);
        if (!$isAuthenticated) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        $user = \Auth::user();


        // Step 3: Thêm thiết bị mới vào hệ thống
        $this->addNewDeviceUseCase->execute(new DeviceData(
            deviceId: $deviceId,
            deviceType: $deviceType,
            userId: $user->id,
        ));

        // Tạo JWT với thông tin thiết bị
        $customClaims = [
            'dev' => [
                'id' => $deviceId,
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
