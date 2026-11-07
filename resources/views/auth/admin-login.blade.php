<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - {{ config('app.name', 'Invitation Card') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen overflow-x-hidden bg-white text-gray-900">
<main class="min-h-screen">
    <section class="login-panel flex min-h-screen w-full items-center justify-center bg-gray-50 px-6 py-12 sm:px-10">
        <div class="login-card w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm sm:p-10">
            <div class="mb-8 flex items-center justify-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500 text-lg font-bold text-white shadow-sm">IC</span>
                <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'Invitation Card') }}</span>
            </div>

            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 text-center">Sign in to your account</h2>
            <p class="mt-2 text-sm text-gray-500 text-center">Enter your credentials to continue.</p>

            @if($errors->any())
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('login.submit') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block text-sm font-medium text-gray-700">
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com" class="mt-2 w-full rounded-lg border border-gray-300 px-3.5 py-3 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                </label>

                <label class="block text-sm font-medium text-gray-700">
                    Password
                    <input type="password" name="password" required placeholder="Enter your password" class="mt-2 w-full rounded-lg border border-gray-300 px-3.5 py-3 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                </label>

                <button class="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                    Sign in
                </button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
