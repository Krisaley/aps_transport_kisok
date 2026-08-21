<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use App\Models\Role;

new #[Title('Roles')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $sortDirection = 'ASC';
    public string $sortBy = 'name';
    public string $statusFilter = 'all';
    public ?int $disableRoleId = null;
    public ?string $disableRoleName = null;
    public ?string $disableRoleStatus = null;

    public function sort(string $sortBy): void
    {
        if ($this->sortBy === $sortBy)
        {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
            return;
        }

        $this->sortBy = $sortBy;
        $this->sortDirection = 'ASC';
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'search',
            'statusFilter',
        ])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'statusFilter',
        ]);
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'roles' => Role::query()
                ->where('name', '!=', 'Super-Admin')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%');
                    });
                })
                ->when(
                    $this->statusFilter === 'enabled',
                    fn ($query) => $query->where('is_active', true)
                )
                ->when(
                    $this->statusFilter === 'disabled',
                    fn ($query) => $query->where('is_active', false)
                )
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10),
        ];
    }

    public function confirmDisable(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        abort_if($role->name === 'Super-Admin', 403);

        $this->disableRoleId = $role->id;
        $this->disableRoleName = $role->name;
        $this->disableRoleStatus = $role->isActive() ? 'disable' : 'enable';

        Flux::modal('disable-role')->show();
    }

    public function disableRole()
    {
        Gate::authorize('admin.roles.update');

        $role = Role::findOrFail($this->disableRoleId);

        abort_if($role->name === 'Super-Admin', 403);
        // cjr - make tidy

        $role->update([
            'is_active' => ! $role->isActive(),
        ]);

        $status = $role->is_active ? 'enabled' : 'disabled';

        $this->reset([
            'disableRoleId',
            'disableRoleName',
            'disableRoleStatus',
        ]);

        Flux::toast(
            text: "Role {$status} successfully",
            variant: 'success',
        );

        Flux::modal('disable-role')->close();
    }

    public function activeFilterCount(): int
    {
        return collect([
            $this->statusFilter !== 'all',
        ])->filter()->count();
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Roles',
    ])

    <flux:heading class="sr-only">{{ __('Roles') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-5xl')"
        >
        
        <div class="space-y-4">

            {{-- Toolbar --}}
            <div class="flex items-center justify-between gap-4">

                <div class="flex flex-1 items-center gap-2">
                    <div class="w-full max-w-sm">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            icon="magnifying-glass"
                            placeholder="Search roles..."
                        />
                    </div>

                    <flux:dropdown>
                        <flux:button icon:trailing="funnel">
                            {{ __('Filters') }}

                            @if ($this->activeFilterCount() > 0)
                                <flux:badge size="sm">
                                    {{ $this->activeFilterCount() }}
                                </flux:badge>
                            @endif
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item wire:click="clearFilters">
                                {{ __('Clear Filters') }}
                            </flux:menu.item>

                            <flux:menu.submenu heading="{{ __('Status') }}">
                                <flux:menu.radio.group wire:model.live="statusFilter">
                                    <flux:menu.radio value="all">{{ __('All') }}</flux:menu.radio>
                                    <flux:menu.radio value="enabled">{{ __('Enabled') }}</flux:menu.radio>
                                    <flux:menu.radio value="disabled">{{ __('Disabled') }}</flux:menu.radio>
                                </flux:menu.radio.group>
                            </flux:menu.submenu>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="flex shrink-0 gap-2">
                    @can('admin.role.create')
                        <flux:button
                            variant="primary"
                            icon="plus"
                            :href="route('settings.roles.create')"
                        >
                            {{ __('Add Role') }}
                        </flux:button>
                    @endcan
                </div>

            </div>

            {{-- Table --}}
            <div class="space-y-6">
                <flux:table :paginate="$roles" :pagination:scroll-to>
                    <flux:table.columns sticky>
                        <flux:table.column class="flex items-center gap-3">#</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Members') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>


                    <flux:table.rows>
                        @forelse ($roles as $role)
                            <flux:table.row :key="$role->id">
                                <flux:table.cell class="w-16 text-center">{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($role->isActive())
                                        <flux:icon.check-circle class="text-green-500" />
                                    @else
                                        <flux:icon.x-circle class="text-red-500" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $role->name }}</flux:table.cell>
                                <flux:table.cell>{{ $role->users()->count() }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:dropdown size="sm" variant="ghost" position="bottom" align="end">
                                        <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                        <flux:menu>
                                            @can('admin.role.permissions')
                                                <flux:menu.item icon="shield-exclamation" :href="route('settings.roles.permissions', $role)">{{ __('Permissions') }}</flux:menu.item>
                                            @endcan
                                            @can('admin.role.update')
                                                <flux:menu.item icon="pencil" :href="route('settings.roles.update', $role)">{{ __('Manage') }}</flux:menu.item>
                                            <flux:separator />
                                                @if($role->isActive())
                                                    @if ($role->users()->count() > 0)
                                                        <flux:tooltip content="remove assigned members before disabling">
                                                            <flux:menu.item icon="lock-closed">{{ __('Disable (Blocked)') }}</flux:menu.item>
                                                        </flux:tooltip>
                                                    @else
                                                        <flux:menu.item icon="lock-closed" variant="danger" wire:click="confirmDisable({{ $role->id }})">{{ __('Disable') }}</flux:menu.item>
                                                    @endif
                                                @else
                                                    <flux:menu.item icon="lock-open" wire:click="confirmDisable({{ $role->id }})">{{ __('Enable') }}</flux:menu.item>
                                                @endif
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-4 text-center text-zinc-500">
                                    {{ __('No Roles Found') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

        </div>

        {{-- Disable Role Modal --}}
        <flux:modal name="disable-role" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __(ucwords($disableRoleStatus).' role?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Are you sure you want to '.$disableRoleStatus.' this role?') }}</flux:text>
                    @if ($disableRoleName)
                        <flux:text class="mt-2 font-medium">{{ $disableRoleName }}</flux:text>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button 
                        variant="{{ $disableRoleStatus === 'disable' ? 'danger' : 'primary' }}"
                        wire:click="disableRole"
                    >{{ __(ucwords($disableRoleStatus)) }}</flux:button>
                </div>
            </div>
        </flux:modal>

    </x-pages::settings.layout>
</section>
