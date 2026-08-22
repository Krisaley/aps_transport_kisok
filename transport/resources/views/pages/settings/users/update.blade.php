<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Password;
use Flux\Flux;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use App\Models\Company;
use Illuminate\Validation\Rule;

new #[Title('Update User')] class extends Component {
    
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $role = '';
    public bool $is_active = true;
    public array $selectedRoles = [];
    public array $selectedCompanies = [];
    public ?int $defaultCompanyId = null;
    public ?int $companyToAdd = null;
    public ?string $roleToAdd = null;

    public function addCompany(): void { if ($this->companyToAdd && !in_array($this->companyToAdd,array_map('intval',$this->selectedCompanies),true)) $this->selectedCompanies[]=$this->companyToAdd; $this->companyToAdd=null; Flux::modal('add-company')->close(); }
    public function removeCompany(int $id): void { if ($id===$this->defaultCompanyId) { $this->addError('defaultCompanyId','Choose a different default before removing this company.'); return; } $this->selectedCompanies=array_values(array_filter($this->selectedCompanies,fn($value)=>(int)$value!==$id)); }
    public function addRole(): void { if ($this->roleToAdd && !in_array($this->roleToAdd,$this->selectedRoles,true)) $this->selectedRoles[]=$this->roleToAdd; $this->roleToAdd=null; Flux::modal('add-role')->close(); }
    public function removeRole(string $name): void { $this->selectedRoles=array_values(array_filter($this->selectedRoles,fn($value)=>$value!==$name)); }

    public function mount(User $user):void
    {
        abort_if($user->email === 'super@admin.user', 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;

        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->selectedCompanies = $user->companies()->pluck('companies.id')->push($user->company_id)->filter()->unique()->values()->all();
        $this->defaultCompanyId = $user->company_id;
    }

    public function updatedDefaultCompanyId($value): void
    {
        if ($value && ! in_array((int) $value, array_map('intval', $this->selectedCompanies), true)) $this->selectedCompanies[] = (int) $value;
    }

    public function save()
    {
        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
            'is_active'         => ['boolean'],
            'selectedRoles'     => ['array'],
            'selectedRoles.*'   => ['string', 'exists:roles,name'],
            'selectedCompanies' => ['required', 'array', 'min:1'],
            'selectedCompanies.*' => ['integer', Rule::exists('companies', 'id')->where('is_active', true)],
            'defaultCompanyId' => ['required', 'integer', Rule::in(array_map('intval', $this->selectedCompanies))],
        ]);

        $this->user->update([
            'name'  => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'company_id' => $this->defaultCompanyId,
        ]);
        $this->user->companies()->sync(array_map('intval', $this->selectedCompanies));

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
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),

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
                <div class="space-y-3"><div class="flex items-center justify-between"><flux:heading size="md">Companies / tenants</flux:heading><flux:modal.trigger name="add-company"><flux:button type="button" size="sm" icon="plus">Add tenant</flux:button></flux:modal.trigger></div><flux:table><flux:table.columns><flux:table.column>Tenant</flux:table.column><flux:table.column>Default</flux:table.column><flux:table.column></flux:table.column></flux:table.columns><flux:table.rows>@foreach($companies->whereIn('id',array_map('intval',$selectedCompanies)) as $company)<flux:table.row :key="$company->id"><flux:table.cell>{{ $company->name }}</flux:table.cell><flux:table.cell><flux:radio wire:model.live="defaultCompanyId" :value="$company->id" label="Default"/></flux:table.cell><flux:table.cell align="end"><flux:button type="button" size="xs" variant="danger" wire:click="removeCompany({{ $company->id }})">Remove</flux:button></flux:table.cell></flux:table.row>@endforeach</flux:table.rows></flux:table><flux:error name="selectedCompanies"/><flux:error name="defaultCompanyId"/></div>
                <div class="space-y-3">
                    <flux:heading size="md">{{ __('Roles') }}</flux:heading>
                    <div class="flex justify-end"><flux:modal.trigger name="add-role"><flux:button type="button" size="sm" icon="plus">Add role</flux:button></flux:modal.trigger></div><flux:table><flux:table.columns><flux:table.column>Role</flux:table.column><flux:table.column></flux:table.column></flux:table.columns><flux:table.rows>@foreach($selectedRoles as $selectedRole)<flux:table.row :key="$selectedRole"><flux:table.cell>{{ $selectedRole }}</flux:table.cell><flux:table.cell align="end"><flux:button type="button" size="xs" variant="danger" wire:click="removeRole('{{ $selectedRole }}')">Remove</flux:button></flux:table.cell></flux:table.row>@endforeach</flux:table.rows></flux:table>
                </div>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('settings.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>

        {{-- Reset Password Modal --}}
        <flux:modal name="add-company" class="min-w-[24rem]"><div class="space-y-5"><flux:heading size="lg">Assign tenant</flux:heading><flux:select wire:model="companyToAdd" variant="listbox" searchable label="Tenant"><flux:select.option value="">Select tenant</flux:select.option>@foreach($companies->whereNotIn('id',array_map('intval',$selectedCompanies)) as $company)<flux:select.option :value="$company->id">{{ $company->name }}</flux:select.option>@endforeach</flux:select><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="addCompany">Add</flux:button></div></div></flux:modal>
        <flux:modal name="add-role" class="min-w-[24rem]"><div class="space-y-5"><flux:heading size="lg">Assign role</flux:heading><flux:select wire:model="roleToAdd" variant="listbox" searchable label="Role"><flux:select.option value="">Select role</flux:select.option>@foreach($roles->whereNotIn('name',$selectedRoles) as $role)<flux:select.option :value="$role->name">{{ $role->name }}</flux:select.option>@endforeach</flux:select><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="addRole">Add</flux:button></div></div></flux:modal>
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
