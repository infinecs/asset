<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Infinecs Asset &amp; Employee Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.ico') }}">
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white font-sans antialiased dark:bg-slate-950" x-data="{
        dark: localStorage.getItem('theme') === 'dark',
        toggleTheme() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('theme', this.dark ? 'dark' : 'light'); }
    }">
    <div class="flex min-h-full">

        {{-- Branding panel --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-900 via-blue-950 to-slate-950 p-12 text-white lg:flex">
            <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-blue-400/20 blur-3xl"></div>

            <img src="{{ asset('images/infinecs-logo-white.png') }}" alt="Infinecs" class="relative h-9 w-fit object-contain">

            <div class="relative">
                <h1 class="text-3xl font-bold leading-tight xl:text-4xl">Every asset.<br>Every employee.<br>One platform.</h1>
                <p class="mt-3 max-w-sm text-sm text-blue-100">From hardware and licenses to the people they're assigned to — manage your whole IT and workforce lifecycle in one place.</p>

                <div class="mt-10 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><i class="bi bi-laptop"></i></span>
                        <span class="text-sm text-blue-50">Full asset lifecycle tracking</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><i class="bi bi-people"></i></span>
                        <span class="text-sm text-blue-50">Employee directory &amp; offboarding</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><i class="bi bi-key"></i></span>
                        <span class="text-sm text-blue-50">Digital license management</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><i class="bi bi-file-earmark-check"></i></span>
                        <span class="text-sm text-blue-50">E-signature custody agreements</span>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-blue-200/60">Asset &amp; Employee Management System &copy; {{ date('Y') }} Infinecs</p>
        </div>

        {{-- Login form panel --}}
        <div class="relative flex w-full flex-col justify-center px-6 py-12 sm:px-10 lg:w-1/2 lg:px-16">
            <button type="button" class="absolute right-5 top-5 rounded-lg border border-slate-300 p-1.5 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="toggleTheme()" title="Toggle dark mode">
                <i class="bi" :class="dark ? 'bi-sun-fill' : 'bi-moon-stars-fill'"></i>
            </button>

            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8">
                    <img src="{{ asset('images/infinecs-logo.png') }}" alt="Infinecs" class="mb-6 h-8 w-fit rounded bg-white object-contain p-1 lg:hidden">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Welcome back</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sign in to your account to continue</p>
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

                <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPassword: false }">
                    @csrf
                    <div>
                        <label class="field-label">Email Address</label>
                        <div class="relative">
                            <i class="bi bi-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="email" name="email" class="field-input pl-9 focus:!border-blue-600 focus:!ring-blue-600 @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                        </div>
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label">Password</label>
                        <div class="relative">
                            <i class="bi bi-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input :type="showPassword ? 'text' : 'password'" name="password" class="field-input px-9 focus:!border-blue-600 focus:!ring-blue-600" placeholder="••••••••" required>
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" @click="showPassword = !showPassword" tabindex="-1">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-blue-700" name="remember" id="remember">
                        <label class="text-sm text-slate-600 dark:text-slate-300" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn w-full bg-blue-700 py-2.5 font-semibold text-white hover:bg-blue-800 focus-visible:ring-blue-500">
                        <i class="bi bi-box-arrow-in-right"></i>Sign In
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-500 lg:hidden">Asset &amp; Employee Management System &copy; {{ date('Y') }} Infinecs</p>
            </div>
        </div>

    </div>
</body>
</html>
