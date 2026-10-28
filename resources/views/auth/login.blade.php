@php($company = \App\Models\Setting::get('company_name', 'MK DUA PA'))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ $company }}</title>
    <x-favicons />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-ink-50 px-4 py-10">
<main class="w-full max-w-sm">
    <img src="{{ asset('images/logo.png') }}" alt="{{ $company }}" class="mx-auto h-24 w-auto">

    <form method="POST" action="{{ url('/login') }}" class="card mt-8 space-y-4 border-t-4 border-t-gold-500 p-6 sm:p-8">
        @csrf
        <div>
            <h1 class="font-display text-xl font-bold text-ink-900">Welcome back</h1>
            <p class="mt-1 text-sm text-ink-500">Sign in to continue.</p>
        </div>
        <x-field label="Email address" name="email" type="email" required autofocus autocomplete="username" />
        <x-field label="Password" name="password" type="password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" name="remember" class="checkbox"> Keep me signed in</label>
        <button class="btn btn-primary w-full py-3">Sign in</button>
    </form>

    <p class="mt-6 text-center text-xs text-ink-500">Forgot your password? Ask your administrator to reset it.</p>
</main>
</body>
</html>
