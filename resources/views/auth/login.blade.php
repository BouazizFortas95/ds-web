@php
    $locale = app()->getLocale();
    $textDirection = $textDirection ?? App\Support\Locales::direction($locale);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $textDirection }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('site.auth.login_title') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap');
            body { background: #050505; color: #E2E8F0; font-family: 'Cairo', 'Noto Sans Arabic', sans-serif; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        </style>
    </head>
    <body class="min-h-screen flex items-center justify-center p-6">
        <main class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900/80 p-8 shadow-[0_0_32px_rgba(0,240,255,0.12)]">
            <header class="mb-6 text-center">
                <a href="{{ route('home') }}" class="inline-block text-2xl font-black text-cyan-300 hover:text-cyan-200">{{ __('site.common.brand_name') }}</a>
                <div class="mono mt-2 text-xs tracking-[0.24em] text-slate-400">{{ __('site.auth.secure_access') }}</div>
            </header>

            <div class="mb-6 flex justify-center">
                <x-language-switcher />
            </div>

            {{-- Errors and flash status. Both are rendered inline rather than
                 flashed to the session banner so the message stays next to the
                 form the visitor has to correct. --}}
            @if (session('status'))
                <div role="status" class="mb-5 rounded-xl border border-[#39FF14]/50 bg-[#39FF14]/10 px-4 py-3 text-sm text-[#dfffe2]">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-xl border border-red-500/50 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- POST + CSRF. The form used to carry neither, so submitting it
                 issued `GET /login?email=...&password=...` and leaked the
                 credentials into the URL. --}}
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm text-slate-300">{{ __('site.auth.email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        @class([
                            'w-full rounded-xl border bg-slate-950/80 px-4 py-3 text-slate-100 outline-none ring-0 transition focus:border-cyan-400',
                            'border-red-500/70' => $errors->has('email'),
                            'border-slate-700' => ! $errors->has('email'),
                        ])
                        placeholder="{{ __('site.auth.email_placeholder') }}"
                        dir="ltr"
                    />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm text-slate-300">{{ __('site.auth.password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        @class([
                            'w-full rounded-xl border bg-slate-950/80 px-4 py-3 text-slate-100 outline-none ring-0 transition focus:border-cyan-400',
                            'border-red-500/70' => $errors->has('password'),
                            'border-slate-700' => ! $errors->has('password'),
                        ])
                        placeholder="{{ __('site.auth.password_placeholder') }}"
                        dir="ltr"
                    />
                </div>

                <label for="remember" class="flex items-center gap-2 text-sm text-slate-400">
                    <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-950 accent-cyan-400" />
                    {{ __('site.auth.remember_me') }}
                </label>

                <button type="submit" class="w-full rounded-xl border border-[#39FF14]/70 bg-[#39FF14]/10 px-4 py-3 font-bold text-[#dfffe2] shadow-[0_0_24px_rgba(57,255,20,0.18)] transition hover:shadow-[0_0_28px_rgba(57,255,20,0.28)]">
                    {{ __('site.common.login') }}
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-400">
                {{ __('site.auth.no_account') }}
                <a href="{{ route('register') }}" class="text-cyan-300 hover:text-cyan-200">{{ __('site.auth.register_link') }}</a>
            </div>
        </main>
    </body>
</html>
