<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <header class="bg-blue-600 p-4 shadow-lg text-white">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">Prep Learning</h1>
        </div>
    </header>

    <main class="container mx-auto mt-10">
        @yield('content')
    </main>

    <footer class="bg-gray-800 text-white mt-10 p-4 text-center">
        <p>&copy; 2024 Prep Learning</p>
    </footer>

</body>
</html>