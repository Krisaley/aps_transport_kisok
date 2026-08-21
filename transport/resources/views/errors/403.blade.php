<x-layouts::app>
    <section class="flex min-h-[60vh] w-full items-center justify-center px-6">
        <div class="w-full max-w-xl text-center">
            <flux:heading size="xl">
                {{ __('403 - Access denied') }}
            </flux:heading>

            <flux:text class="mt-3">
                {{ $exception->getMessage() ?: __('You do not have permission to access this page or perform this action.') }}
            </flux:text>

            <div class="mt-6 flex justify-center gap-3">
                <flux:button
                    href="{{ url()->previous() }}"
                    variant="primary"
                >
                    {{ __('Go back') }}
                </flux:button>

                <flux:button
                    href="{{ route('dashboard') }}"
                    variant="ghost"
                >
                    {{ __('Dashboard') }}
                </flux:button>
            </div>
        </div>
    </section>
</x-layouts::app>