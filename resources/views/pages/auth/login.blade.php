<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-5">

        <div>
            <h2 class="text-lg font-bold text-white">{{ __('Sign in') }}</h2>
            <p class="mt-0.5 text-sm text-zinc-500">{{ __('Enter your credentials to access your account') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                       class="absolute end-0 top-0 text-xs text-zinc-500 hover:text-zinc-300 transition-colors">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <button
                type="submit"
                data-test="login-button"
                class="mt-1 w-full rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-900/50 transition hover:from-indigo-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-transparent"
            >
                {{ __('Sign in') }} →
            </button>
        </form>

        @if (Route::has('register'))
            <p class="text-center text-sm text-zinc-600">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register') }}" wire:navigate class="font-medium text-zinc-400 hover:text-white transition-colors">
                    {{ __('Sign up') }}
                </a>
            </p>
        @endif

    </div>
</x-layouts::auth>
