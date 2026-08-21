<?php

use Livewire\Component;
use Livewire\Attributes\Title;


new #[Title('Users')] class extends Component {
    //
};
?>

<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Dashboard',
    ])

    <flux:heading class="sr-only">{{ __('Settings Dashboard') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-5xl')"
        >

        <p>Dashboard for admin area</p>

    </x-pages::settings.layout>
</section>
