<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Setup')] class extends Component {};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Setup') }}</flux:heading>
    <flux:text class="mt-2">{{ __('Manage the reference data used by transport operations.') }}</flux:text>

    <x-pages::setup.layout contentclass="max-w-5xl">
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Dashboard') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Dashboard content to be confirmed.') }}</flux:text>
        </div>
    </x-pages::setup.layout>
</section>
