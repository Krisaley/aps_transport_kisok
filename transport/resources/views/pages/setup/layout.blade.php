<div class="flex flex-col gap-6 lg:flex-row">
    <aside class="w-full lg:w-56">
        <flux:navlist>
            <flux:navlist.item :href="route('setup.dashboard')" :current="request()->routeIs('setup.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            @foreach (['make' => ['makes', 'Makes'], 'model' => ['models', 'Models'], 'customer' => ['customers', 'Customers'], 'site' => ['sites', 'Sites'], 'equipment' => ['equipment', 'Equipment'], 'vehicle' => ['vehicles', 'Vehicles']] as $area => [$path, $label])
                @can('setup.area', $area)
                    <flux:navlist.item :href="route('setup.'.$path.'.index')" :current="request()->routeIs('setup.'.$path.'.*')" wire:navigate>{{ __($label) }}</flux:navlist.item>
                @endcan
            @endforeach
        </flux:navlist>
    </aside>

    <div class="min-w-0 flex-1 {{ $contentclass ?? '' }}">
        {{ $slot }}
    </div>
</div>
