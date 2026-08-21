<div class="flex flex-col gap-6 lg:flex-row">
    <aside class="w-full lg:w-56">
        <flux:navlist>
            @can('user.area', 'movement')
                <flux:navlist.item :href="route('operations.movements.index')" :current="request()->routeIs('operations.movements.*')" wire:navigate>{{ __('Movements') }}</flux:navlist.item>
            @endcan
        </flux:navlist>
    </aside>

    <div class="min-w-0 flex-1 {{ $contentclass ?? '' }}">
        {{ $slot }}
    </div>
</div>
