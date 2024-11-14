@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <!-- Tạo container cho Vue instance -->
    <div id="app" class="max-w-md mx-auto bg-white p-8 border border-gray-300 shadow-lg rounded-lg">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Register</h2>
        <form @submit.prevent="register"> <!-- Gọi hàm register khi submit -->
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-gray-700">Name:</label>
                <input type="text" name="name" v-model="name" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email:</label>
                <input type="email" name="email" v-model="email" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password:</label>
                <input type="password" name="password" v-model="password" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="block text-gray-700">Confirm Password:</label>
                <input type="password" name="password_confirmation" v-model="passwordConfirmation" class="w-full px-3 py-2 border border-gray-300 rounded" required>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Register</button>
        </form>
    </div>

    <!-- Import Vue.js và Axios từ CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                name: '',
                email: '',
                password: '',
                passwordConfirmation: ''
            },
            methods: {
                register() {
                    // Gửi request đăng ký qua Axios
                    axios.post('/api/register', {
                        name: this.name,
                        email: this.email,
                        password: this.password,
                        password_confirmation: this.passwordConfirmation
                    })
                    .then(response => {
                        // Xử lý thành công: có thể lưu token hoặc chuyển hướng
                        alert('Registration successful!');
                        console.log(response.data);
                        // Ví dụ: lưu token vào localStorage
                        localStorage.setItem('token', response.data.token);
                        // Chuyển hướng (nếu cần)
                        window.location.href = '/login';
                    })
                    .catch(error => {
                        // Xử lý lỗi (ví dụ: hiển thị lỗi đăng ký)
                        console.error(error);
                        if (error.response && error.response.data) {
                            alert(`Registration failed: ${error.response.data.message || 'Please check your input.'}`);
                        } else {
                            alert('Registration failed! Please try again.');
                        }
                    });
                }
            }
        });
    </script>
@endsection
