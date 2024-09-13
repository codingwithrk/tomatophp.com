<div>
    <div>
        {{ $this->form }}

        <div class="my-4 flex flex-col justify-center gap-4">
            <x-cms-main-button type="button" wire:click="submit()" :label="__('Login')" icon="bxs-log-in" />
            <div class="text-center">
                OR
            </div>
            <div class="flex justify-center gap-4">
                <a x-tooltip="{'content': 'Login With Github', theme: $store.theme}" href="{{ route('login.provider', ['provider' => 'github']) }}">
                    <x-icon name="bxl-github" class="w-8 h-8" />
                </a>
                <a x-tooltip="{'content': 'Login With Discord', theme: $store.theme}" href="{{ route('login.provider', ['provider' => 'discord']) }}">
                    <x-icon name="bxl-discord" class="w-8 h-8" />
                </a>
            </div>
            <div class="text-center">
                Don't have account? please <a href="{{ url(app()->getLocale() . '/register') }}" class="text-primary-600 hover:text-primary-800">Register</a>
            </div>
        </div>
    </div>
</div>
