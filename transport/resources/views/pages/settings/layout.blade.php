<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('settings.dashboard')" wire:navigate>{{ __('Admin Dashboard') }}</flux:navlist.item>

            <flux:navlist.group heading="Users, Teams & Roles">
                @can('admin.area', 'user')
                    <flux:navlist.item :href="route('settings.users.index')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                @endcan
                @can('admin.area', 'role')
                    <flux:navlist.item :href="route('settings.roles.index')" wire:navigate>{{ __('Roles') }}</flux:navlist.item>
                @endcan
                @can('admin.conf.update')
                    <flux:navlist.item :href="route('settings.config')" wire:navigate>{{ __('Configuration') }}</flux:navlist.item>
                @endcan
            </flux:navlist.group>
            
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">

        <div class="{{ $contentclass ?? 'mt-5 w-full max-w-5xl' }}">
            {{ $slot }}
        </div>
    </div>
</div>
