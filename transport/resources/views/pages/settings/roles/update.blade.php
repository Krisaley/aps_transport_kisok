<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Role;
use Flux\Flux;

new #[Title('Update Role')] class extends Component {

    public Role $role;

    public string $name             = '';
    public bool $is_active          = true;

    public function mount(Role $role):void
    {
        $this->role            = $role;
        $this->name             = $role->name;
        $this->is_active        = (bool) $role->is_active;
    }

    public function save()
    {
        $this->validate([
            'name'              => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->role->id],
            'is_active'         => ['boolean'],
        ]);

        $this->role->update([
            'name'      => $this->name,
            'is_active' => $this->is_active,
        ]);

        Flux::toast(
            text: 'Role updated successfully',
            variant: 'success',
        );

        return $this->redirectRoute('settings.roles.index', navigate: true);
    }

    public function with(): array
    {
        return [];
    }

};
?>
<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Roles',
    ])

    <flux:heading class="sr-only">{{ __('Manage Role') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Edit Role') }}</flux:heading>
                <flux:button variant="ghost" :href="route('settings.roles.index')" wire:navigate>{{ __('Back') }}</flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input label="Name" placeholder="Name" wire:model="name" />
                <div class="space-y-2">
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:switch wire:model="is_active" label="Role is enabled" />
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('settings.roles.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>

    </x-pages::settings.layout>

</section>
