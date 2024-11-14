@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="max-w-md mx-auto bg-white p-8 border border-gray-300 shadow-lg rounded-lg">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Login</h2>
        <form method="POST" action="/api/login">
            @csrf
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email:</label>
                <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password:</label>
                <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>

            <!-- Device Type Radio Buttons -->
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Device Type:</label>
                <div class="flex items-center">
                    <input type="radio" name="device_type" value="web" id="device_type_web" class="mr-2">
                    <label for="device_type_web" class="mr-4 text-gray-700">Web</label>

                    <input type="radio" name="device_type" value="app" id="device_type_app" class="mr-2">
                    <label for="device_type_app" class="mr-4 text-gray-700">App</label>

                    <input type="radio" name="device_type" value="tablet" id="device_type_tablet" class="mr-2">
                    <label for="device_type_tablet" class="text-gray-700">Tablet</label>
                </div>
            </div>

            <div class="mb-4">
                <label for="device_uuid" class="block text-gray-700">Device UUID:</label>
                <input type="text" name="device_uuid" value="web-unique-id" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="device_name" class="block text-gray-700">Device Name:</label>
                <input type="text" name="device_name" value="My Web Browser" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
        </form>
    </div>
@endsection
