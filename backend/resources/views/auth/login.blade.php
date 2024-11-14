@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <!-- Tạo container cho Vue instance -->
    <div id="app" class="max-w-md mx-auto bg-white p-8 border border-gray-300 shadow-lg rounded-lg">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Login</h2>
        <form @submit.prevent="login"> <!-- Gọi hàm login khi submit -->
            @csrf
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email:</label>
                <input type="email" name="email" v-model="email" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password:</label>
                <input type="password" name="password" v-model="password" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>

            <!-- Device Type Radio Buttons -->
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Device Type:</label>
                <div class="flex items-center">
                    <input type="radio" name="device_type" value="web" id="device_type_web" v-model="deviceType" class="mr-2">
                    <label for="device_type_web" class="mr-4 text-gray-700">Web</label>

                    <input type="radio" name="device_type" value="app" id="device_type_app" v-model="deviceType" class="mr-2">
                    <label for="device_type_app" class="mr-4 text-gray-700">App</label>

                    <input type="radio" name="device_type" value="tablet" id="device_type_tablet" v-model="deviceType" class="mr-2">
                    <label for="device_type_tablet" class="text-gray-700">Tablet</label>
                </div>
            </div>

            <!-- Auto-generated Device UUID -->
            <div class="mb-4">
                <label for="device_uuid" class="block text-gray-700">Device UUID:</label>
                <input type="text" name="device_uuid" v-model="deviceUuid" class="w-full px-3 py-2 border border-gray-300 rounded" readonly>
            </div>

            <!-- Device Name Textarea with User-Agent -->
            <div class="mb-4">
                <label for="device_name" class="block text-gray-700">Device Name (User-Agent):</label>
                <textarea name="device_name" v-model="deviceName" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100" readonly></textarea>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
        </form>
    </div>

    <!-- Import Vue.js, Axios, và uuid từ CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/uuid/8.3.2/uuid.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                email: '',
                password: '',
                deviceType: 'web',                     // Mặc định là 'web'
                deviceUuid: '',                        // Sẽ được gán UUID từ localStorage hoặc tạo mới
                deviceName: navigator.userAgent        // Lấy User-Agent mặc định
            },
            created() {
                // Kiểm tra nếu UUID tồn tại trong localStorage
                const savedUuid = localStorage.getItem('device_uuid');
                if (savedUuid) {
                    this.deviceUuid = savedUuid;
                } else {
                    this.deviceUuid = uuid.v4();
                    localStorage.setItem('device_uuid', this.deviceUuid);
                }
            },
            methods: {
                login() {
                    // Gửi request login qua Axios
                    axios.post('/api/login', {
                        email: this.email,
                        password: this.password,
                        device_type: this.deviceType,
                        device_uuid: this.deviceUuid,
                        device_name: this.deviceName
                    })
                    .then(response => {
                        // Xử lý thành công: có thể lưu token hoặc chuyển hướng
                        alert('Login successful!');
                        console.log(response.data);
                        // Ví dụ: lưu token vào localStorage
                        localStorage.setItem('token', response.data.token);
                        // Chuyển hướng (nếu cần)
                        window.location.href = '/';
                    })
                    .catch(error => {
                        // Xử lý lỗi (ví dụ: hiển thị lỗi đăng nhập)
                        console.error(error);
                        alert('Login failed! Please check your credentials.');
                    });
                }
            }
        });
    </script>
@endsection
