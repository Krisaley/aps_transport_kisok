<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Flux\Flux;
use App\Models\Company;
use Illuminate\Validation\Rule;

new #[Title('Create User')] class extends Component {
    
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $role = '';
    public bool $is_active = true;
    public array $selectedRoles = [];
    public array $selectedTeams = [];
    public array $selectedCompanies = [];
    public ?int $defaultCompanyId = null;

    public function updatedDefaultCompanyId($value): void
    {
        if ($value && ! in_array((int) $value, array_map('intval', $this->selectedCompanies), true)) $this->selectedCompanies[] = (int) $value;
    }

    public function save()
    {
        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'is_active'            => ['boolean'],
            'selectedRoles'     => ['array'],
            'selectedRoles.*'   => ['string', 'exists:roles,name'],
            'selectedCompanies' => ['required', 'array', 'min:1'],
            'selectedCompanies.*' => ['integer', Rule::exists('companies', 'id')->where('is_active', true)],
            'defaultCompanyId' => ['required', 'integer', Rule::in(array_map('intval', $this->selectedCompanies))],
        ]);

        $user = User::create([
            'name'      => $this->name,
            'email'     => $this->email,
            'is_active' => $this->is_active,
            'password'  => Hash::make(Str::random(32)),
            'company_id' => $this->defaultCompanyId,
        ]);
        $user->companies()->sync(array_map('intval', $this->selectedCompanies));
        $user->syncRoles($this->selectedRoles);

        Password::sendResetLink([
            'email' => $user->email,
        ]);

        Flux::toast(
            text: 'User created and password setup link sent',
            variant: 'success',
        );

        return $this->redirectRoute('settings.users.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'roles' => Role::query()
                ->where('name', '!=', 'Super-Admin')
                ->orderBy('name')
                ->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ];
    }

};
?>
<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Users',
    ])

    <flux:heading class="sr-only">{{ __('Create User') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >
    
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Create User') }}</flux:heading>
                <flux:button variant="ghost" :href="route('settings.users.index')" wire:navigate>{{ __('Back') }}</flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input label="Name" placeholder="Name" wire:model="name" />
                <flux:input label="Email" placeholder="Email" wire:model="email" />
                <div class="space-y-2">
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:switch wire:model="is_active" label="User can login" />
                </div>
                <div class="space-y-3"><flux:heading size="md">Companies / tenants</flux:heading><flux:text>Choose every company this user may access, then set their default.</flux:text><div class="grid gap-3 sm:grid-cols-2">@foreach($companies as $company)<label class="flex items-center gap-2"><flux:checkbox wire:model="selectedCompanies" :value="$company->id"/><span>{{ $company->name }}</span></label>@endforeach</div><flux:select wire:model.live="defaultCompanyId" label="Default company"><flux:select.option value="">Select default</flux:select.option>@foreach($companies as $company)<flux:select.option :value="$company->id">{{ $company->name }}</flux:select.option>@endforeach</flux:select><flux:error name="selectedCompanies"/><flux:error name="defaultCompanyId"/></div>
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

    </x-pages::settings.layout>

</section>
