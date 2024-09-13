<?php

namespace App\Livewire;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    public array $data = [];

    public function mount()
    {
        $this->form->fill([
            'loginBy' => 'register',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Register Your SaaS Demo')
                ->description('Please use your email and password to register your account.')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Discord username')
                    ->required()
                    ->unique(table:'tenants', ignoreRecord: true)->live(onBlur: true)
                    ->columnSpanFull()
                    ->afterStateUpdated(function(Forms\Set $set, $state) {
                        $set('id', $slug = \Str::of($state)->slug('_')->toString());
                        $set('domain', \Str::of($state)->slug()->toString());
                    }),
                Forms\Components\TextInput::make('id')
                    ->disabled()
                    ->label('Unique ID')
                    ->required()
                    ->unique(table: 'tenants', ignoreRecord: true),
                Forms\Components\TextInput::make('domain')
                    ->disabled()
                    ->label('Sub-Domain')
                    ->required()
                    ->unique(table: 'domains',ignoreRecord: true)
                    ->prefix('https://')
                    ->suffix(".".request()->getHost())
                ,
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email(),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->tel(),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->rule(Password::default())
                    ->autocomplete('new-password')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                    ->live(debounce: 500)
                    ->same('passwordConfirmation'),
                Forms\Components\TextInput::make('passwordConfirmation')
                    ->label('Password Confirmation')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->dehydrated(false),
                Forms\Components\CheckboxList::make('packages')
                    ->searchable()
                    ->label('Plugins')
                    ->hint('Select the plugins you want to install')
                    ->columnSpanFull()
                    ->required()
                    ->default(["filament-users"])
                    ->view('components.packages')
                    ->descriptions(collect(config('app.packages'))->pluck('description', 'key')->toArray())
                    ->options(collect(config('app.packages'))->pluck('label', 'key')->toArray()),
            ])
        ])->statePath('data');
    }

    public function submit()
    {
        $this->form->validate();

        $data = $this->form->getState();

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $otp = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
        $data['id'] = \Str::of($data['name'])->slug('_')->toString();
        $data['domain'] =  \Str::of($data['name'])->slug()->toString();
        session()->put('demo_user', json_encode($data));
        session()->put('demo_otp', $otp);

        Notification::make()
            ->title('New Demo User')
            ->body(collect([
                'NAME: '.$data['name'],
                'EMAIL: '.$data['email'],
                'USERNAME: '.$data['domain'],
                'OTP: '.$otp,
                'PACKAGES: '.collect($data['packages'])->implode(','),
                'URL: '.'https://'.\Str::of($data['name'])->slug()->toString().'.'.config('app.domain'),
            ])->implode("\n"))
            ->sendToDiscord();

        try {
            $embeds = [];
            $embeds['description'] = "your OTP is: ". $otp;
            $embeds['url'] = url('/otp');

            $params = [
                'content' => "@" . $data['domain'],
                'embeds' => [
                    $embeds
                ]
            ];

            Http::post(config('services.discord.otp-webhook'), $params)->json();

        }catch (\Exception $e){
            Notification::make()
                ->title('Something went wrong')
                ->danger()
                ->send();
        }

        Notification::make()
            ->title('Check discord server')
            ->body('We have sent your OTP to our discord server #otp channel')
            ->success()
            ->send();


        return redirect()->route(app()->getLocale() . '.auth.otp');
    }

    public function render()
    {
        return view('livewire.register');
    }
}
