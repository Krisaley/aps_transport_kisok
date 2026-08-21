<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use Flux\Flux;

new #[Title('Update Permissions')] class extends Component {

    public Role $role;

    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        $this->role = $role;

        $this->selectedPermissions = $role->permissions
            ->pluck('name')
            ->all();
    }

    public function save(): mixed
    {
        $this->validate([
            'selectedPermissions' => ['array'],

            'selectedPermissions.*' => [
                'string',

                Rule::exists('permissions', 'name')
                    ->where('guard_name', $this->role->guard_name),
            ],
        ]);

        $this->role->syncPermissions($this->selectedPermissions);

        Flux::toast(
            text: 'Permissions updated successfully',
            variant: 'success',
        );

        return $this->redirectRoute(
            'settings.roles.index',
            navigate: true,
        );
    }

    public function with(): array
    {
        $permissions = Permission::query()
            ->where('guard_name', $this->role->guard_name)
            ->orderBy('name')
            ->get();

        return [
            'permissions' => $permissions,
        ];
    }
};
?>
<section class="w-full">
    @include('partials.settings-heading', [
        'section' => 'Roles',
    ])

    <flux:heading class="sr-only">
        {{ __('Manage Role Permissions') }}
    </flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
    >
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">
                        {{ __('Edit Role Permissions') }}
                    </flux:heading>

                    <flux:text>
                        {{ $role->name }}
                    </flux:text>
                </div>

                <flux:button
                    variant="ghost"
                    :href="route('settings.roles.index')"
                    wire:navigate
                >
                    {{ __('Back') }}
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="space-y-4">
                    <flux:heading size="md">
                        {{ __('Permissions') }}
                    </flux:heading>

                    <flux:checkbox.group wire:model="selectedPermissions">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                <flux:checkbox
                                    :value="$permission->name"
                                    :label="$permission->name"
                                    wire:key="permission-{{ $permission->id }}"
                                />
                            @endforeach
                        </div>
                    </flux:checkbox.group>

                    <flux:error name="selectedPermissions" />
                    <flux:error name="selectedPermissions.*" />
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button
                        variant="ghost"
                        :href="route('settings.roles.index')"
                        wire:navigate
                    >
                        {{ __('Cancel') }}
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </x-pages::settings.layout>
</section>