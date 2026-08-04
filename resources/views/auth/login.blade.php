<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Infinecs Asset Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-800 to-slate-600 px-4 font-sans">
    <div class="w-full max-w-[420px]">
        <div class="card rounded-2xl shadow-2xl shadow-black/30">
            <div class="p-8 sm:p-10">
                <div class="mb-6 text-center">
                    <img src="{{ asset('images/infinecs-logo.png') }}" alt="Infinecs" class="mx-auto mb-3 h-12">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Sign in to your account</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle mt-0.5"></i>
                    <div class="flex-1">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="field-label">Email Address</label>
                        <div class="relative">
                            <i class="bi bi-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="email" name="email" class="field-input pl-9 @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                        </div>
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label">Password</label>
                        <div class="relative">
                            <i class="bi bi-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="password" name="password" class="field-input pl-9" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" name="remember" id="remember">
                        <label class="text-sm text-slate-600 dark:text-slate-300" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full py-2.5 font-semibold">
                        <i class="bi bi-box-arrow-in-right"></i>Sign In
                    </button>
                </form>
            </div>
        </div>
        <p class="mt-4 text-center text-sm text-white/60">IT Asset Management System &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
