<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Password;
use Flux\Flux;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;

new #[Title('Update User')] class extends Component {
    
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $role = '';
    public bool $is_active = true;
    public array $selectedRoles = [];

    public function mount(User $user):void
    {
        abort_if($user->email === 'super@admin.user', 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;

        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    public function save()
    {
        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
            'is_active'         => ['boolean'],
            'selectedRoles'     => ['array'],
            'selectedRoles.*'   => ['string', 'exists:roles,name'],
        ]);

        $this->user->update([
            'name'  => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
        ]);

        $this->user->syncRoles($this->selectedRoles);

        Flux::toast(
            text: 'User updated successfully',
            variant: 'success',
        );

        return $this->redirectRoute('settings.users.index', navigate: true);
    }

    public function confirmPasswordReset(): void
    {
        Flux::modal('reset-password')->show();
    }

    public function sendPasswordResetLink(): void
    {
        $status = Password::sendResetLink([
            'email' => $this->email,
        ]);

        if ($status === Password::RESET_LINK_SENT)
        {
            Flux::toast(
                text: 'Password reset link sent.',
                variant: 'success',
            );
            return;
        }

        Flux::toast(
            text: 'Unable to send password reset link.',
            variant: 'danger',
        );

        Flux::modal('reset-password')->close();
    }

    public function hasActivePasswordResetLink(): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        if (! $record) {
            return false;
        }

        $expiresInMinutes = config('auth.passwords.users.expire', 60);

        return Carbon::parse($record->created_at)
            ->addMinutes($expiresInMinutes)
            ->isFuture();
    }

    public function passwordResetExpiresAt(): ?string
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        if (! $record) {
            return null;
        }

        $expiresInMinutes = config('auth.passwords.users.expire', 60);

        return Carbon::parse($record->created_at)
            ->addMinutes($expiresInMinutes)
            ->diffForHumans();
    }

    public function with(): array
    {
        return [
            'roles' => Role::query()
                ->where('name', '!=', 'Super-Admin')
                ->orderBy('name')
                ->get(),

            'hasActivePasswordResetLink' => $this->hasActivePasswordResetLink(),

            'passwordResetExpiresAt' => $this->passwordResetExpiresAt(),
        ];
    }

};
?>
<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Users',
    ])

    <flux:heading class="sr-only">{{ __('Manage User') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >
    
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
                <flux:button variant="ghost" :href="route('settings.users.index')" wire:navigate>{{ __('Back') }}</flux:button>
            </div>

            @if ($hasActivePasswordResetLink)
                <flux:callout variant="warning" icon="key">
                    {{ __('This user already has an active password reset link.') }}

                    @if ($passwordResetExpiresAt)
                        {{ __('It expires') }} {{ $passwordResetExpiresAt }}.
                    @endif
                </flux:callout>
            @endif

            <form wire:submit="save" class="space-y-6">
                <flux:input label="Name" placeholder="Name" wire:model="name" />
                <flux:input label="Email" placeholder="Email" wire:model="email" />
                <div class="space-y-2">
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:switch wire:model="is_active" label="User can login" />
                </div>
                <div class="space-y-3">
                    <flux:heading size="md">{{ __('Roles') }}</flux:heading>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($roles as $role)
                            <flux:checkbox wire:model="selectedRoles" value="{{ $role->name }}" />
                            <span class="text-sm">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('settings.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>

        {{-- Reset Password Modal --}}
        <flux:modal name="reset-password" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Send Password Reset Link') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Send password reset link to user?') }}</flux:text>
                </div>
                <div class="flex justify-end-gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="sendPasswordResetLink">{{ __('Reset Password') }}</flux:button>
                </div>
            </div>
        </flux:modal>

    </x-pages::settings.layout>

</section>