<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="text-gray-300" />
            <x-text-input id="name" class="block mt-1 w-full p-2 bg-gray-700 border-gray-600 text-white placeholder-gray-400 rounded-md focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" class="text-gray-300" />
            <x-text-input id="email" class="block mt-1 w-full p-2 bg-gray-700 border-gray-600 text-white placeholder-gray-400 rounded-md focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-gray-300" />
            <x-text-input id="password" class="block mt-1 w-full p-2 bg-gray-700 border-gray-600 text-white placeholder-gray-400 rounded-md focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-gray-300" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full p-2 bg-gray-700 border-gray-600 text-white placeholder-gray-400 rounded-md focus:ring focus:ring-blue-500 focus:border-blue-500"
                          type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500" />
        </div>

        <!-- Already registered link and Register button -->
        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-400 hover:text-blue-400" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <x-primary-button class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-500">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
