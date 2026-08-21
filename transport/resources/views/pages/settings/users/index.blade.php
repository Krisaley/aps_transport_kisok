<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Role;

new #[Title('Users')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $sortDirection = 'ASC';
    public string $sortBy = 'name';
    public string $statusFilter = 'all';
    public string $roleFilter = '';
    public ?int $disableUserId = null;
    public ?string $disableUserName = null;
    public ?string $disableUserStatus = null;

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
            'roleFilter',
        ])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'statusFilter',
            'roleFilter',
        ]);
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'users' => User::query()
                ->with([
                    'roles',
                ])
                ->where('email', '!=', 'super@admin.user')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($query) {
                        $query->where('email', 'like', '%'.$this->search.'%')
                            ->orWhere('name', 'like', '%'.$this->search.'%');
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
                ->when(
                    $this->roleFilter !== '',
                    fn ($query) => $query->whereHas(
                        'roles',
                        fn ($query) => $query->whereKey($this->roleFilter)
                    )
                )
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10),
            'roles' => Role::query()
                ->where('name', '!=', 'Super-Admin')
                ->orderBy('name')
                ->get(),
        ];
    }

    public function confirmDisable(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->email === 'super@admin.user', 403);

        $this->disableUserId = $user->id;
        $this->disableUserName = $user->name;
        $this->disableUserStatus = $user->isActive() ? 'disable' : 'enable';

        Flux::modal('disable-user')->show();
    }

    public function disableUser()
    {
        Gate::authorize('admin.users.update');

        $user = User::findOrFail($this->disableUserId);

        abort_if($user->email === 'super@admin.user', 403);
        // cjr - make tidy

        $user->update([
            'is_active' => ! $user->isActive(),
        ]);

        $status = $user->is_active ? 'enabled' : 'disabled';

        $this->reset([
            'disableUserId',
            'disableUserName',
            'disableUserStatus',
        ]);

        Flux::toast(
            text: "User {$status} successfully",
            variant: 'success',
        );

        Flux::modal('disable-user')->close();
    }

    public function activeFilterCount(): int
    {
        return collect([
            $this->statusFilter !== 'all',
            $this->roleFilter   !== '',
        ])->filter()->count();
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Users',
    ])

    <flux:heading class="sr-only">{{ __('Users') }}</flux:heading>

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
                            placeholder="Search users or units..."
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

                            <flux:menu.submenu heading="{{ __('Roles') }}">
                                <flux:menu.radio.group wire:model.live="roleFilter">
                                    <flux:menu.radio value="">{{ __('All Roles') }}</flux:menu.radio>

                                    @foreach ($roles as $role)
                                        <flux:menu.radio value="{{ $role->id }}">
                                            {{ $role->name }}
                                        </flux:menu.radio>
                                    @endforeach
                                </flux:menu.radio.group>
                            </flux:menu.submenu>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="flex shrink-0 gap-2">
                    @can('admin.user.create')
                        <flux:button
                            variant="primary"
                            icon="plus"
                            :href="route('settings.users.create')"
                        >
                            {{ __('Add User') }}
                        </flux:button>
                    @endcan
                </div>

            </div>

            {{-- Table --}}
            <div class="space-y-6">
                <flux:table :paginate="$users" :pagination:scroll-to>
                    <flux:table.columns sticky>
                        <flux:table.column class="flex items-center gap-3">#</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('eMail') }}</flux:table.column>
                        <flux:table.column>{{ __('Roles') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>


                    <flux:table.rows>
                        @forelse ($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell class="w-16 text-center">{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($user->isActive())
                                        <flux:icon.check-circle class="text-green-500" />
                                    @else
                                        <flux:icon.x-circle class="text-red-500" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap max-w-32 truncate">
                                    {{ $user->roles->pluck('name')->join(', ') ?: 'No Role' }}
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:dropdown size="sm" variant="ghost" position="bottom" align="end">
                                        <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil" :href="route('settings.users.update', $user)">{{ __('Manage') }}</flux:menu.item>
                                            <flux:separator />
                                            @can('admin.user.update')
                                                @if($user->isActive())
                                                    <flux:menu.item icon="lock-closed" variant="danger" wire:click="confirmDisable({{ $user->id }})">{{ __('Disable') }}</flux:menu.item>
                                                @else
                                                    <flux:menu.item icon="lock-open" wire:click="confirmDisable({{ $user->id }})">{{ __('Enable') }}</flux:menu.item>
                                                @endif
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-4 text-center text-zinc-500">
                                    {{ __('No Users Found') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

        </div>

        {{-- Disable User Modal --}}
        <flux:modal name="disable-user" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __(ucwords($disableUserStatus).' User?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Are you sure you want to '.$disableUserStatus.' this user?') }}</flux:text>
                    @if ($disableUserName)
                        <flux:text class="mt-2 font-medium">{{ $disableUserName }}</flux:text>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button 
                        variant="{{ $disableUserStatus === 'disable' ? 'danger' : 'primary' }}"
                        wire:click="disableUser"
                    >{{ __(ucwords($disableUserStatus)) }}</flux:button>
                </div>
            </div>
        </flux:modal>

    </x-pages::settings.layout>
</section>
