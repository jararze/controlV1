<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="bg-gray-800 p-4 rounded-lg shadow-lg max-w-md mx-auto transform transition duration-500 hover:shadow-2xl">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" class="text-gray-300 mb-2" />
            <x-text-input id="email" class="block w-full p-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" :value="__('Password')" class="text-gray-300 mb-2" />
            <x-text-input id="password" class="block w-full p-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4 mb-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-gray-700 border-gray-600 text-blue-600 shadow-sm focus:ring focus:ring-blue-500 focus:ring-offset-gray-800" name="remember">
                <span class="ml-2 text-sm text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <!-- Forgot Password and Login Button -->
        <div class="flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-400 hover:text-blue-400" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-500">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
