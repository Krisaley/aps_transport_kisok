<?php

use App\Models\Company;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Companies')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $deleteCompanyId = null;
    public ?string $deleteCompanyName = null;

    private function accessibleCompanies(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();

        return Company::query()->when(! $user->hasRole('Super-Admin'), function ($query) use ($user) {
            $query->where(fn ($query) => $query->whereKey($user->company_id)->orWhereHas('users', fn ($query) => $query->whereKey($user->id)));
        });
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function confirmDelete(int $companyId): void
    {
        Gate::authorize('admin.company.delete');
        $company = $this->accessibleCompanies()->findOrFail($companyId);
        $this->deleteCompanyId = $company->id;
        $this->deleteCompanyName = $company->name;
        Flux::modal('delete-company')->show();
    }

    public function deleteCompany(): void
    {
        Gate::authorize('admin.company.delete');
        $company = $this->accessibleCompanies()->findOrFail($this->deleteCompanyId);
        abort_if(Company::where('is_active', true)->count() <= 1 && $company->is_active, 422, 'The final active tenant cannot be deleted.');
        $company->delete();
        $this->reset(['deleteCompanyId', 'deleteCompanyName']);
        Flux::modal('delete-company')->close();
        Flux::toast(text: 'Company deleted', variant: 'success');
    }

    public function with(): array
    {
        return ['companies' => $this->accessibleCompanies()->withCount('users')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('code', 'like', '%'.$this->search.'%')))
            ->orderBy('name')->paginate(10)];
    }
};
?>
<section class="w-full">
    @include('partials.settings-heading', ['section' => 'Companies / tenants'])
    <x-pages::settings.layout contentclass="mt-5 w-full max-w-5xl">
        <div class="space-y-5">
            <div class="flex items-center justify-between gap-4">
                <flux:input class="max-w-sm" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search companies..." />
                @can('admin.company.create')<flux:button variant="primary" icon="plus" :href="route('settings.companies.create')">Add company</flux:button>@endcan
            </div>
            <flux:table :paginate="$companies">
                <flux:table.columns><flux:table.column>Iteration</flux:table.column><flux:table.column>Shortcode</flux:table.column><flux:table.column>Company name</flux:table.column><flux:table.column>Users</flux:table.column><flux:table.column align="end">Actions</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse($companies as $company)
                        <flux:table.row :key="$company->id">
                            <flux:table.cell>{{ $companies->firstItem() + $loop->index }}</flux:table.cell>
                            <flux:table.cell class="font-mono">{{ $company->code }}</flux:table.cell>
                            <flux:table.cell><strong>{{ $company->name }}</strong>@if($company->trading_name)<br><span class="text-xs text-zinc-500">{{ $company->trading_name }}</span>@endif</flux:table.cell>
                            <flux:table.cell>{{ $company->users_count }}</flux:table.cell>
                            <flux:table.cell align="end"><flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions for {{ $company->name }}"/><flux:menu>@can('admin.company.update')<flux:menu.item icon="pencil" :href="route('settings.companies.update',$company)">Manage</flux:menu.item>@endcan @can('admin.company.delete')<flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $company->id }})">Delete</flux:menu.item>@endcan</flux:menu></flux:dropdown></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5" class="text-center">No companies found</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        <flux:modal name="delete-company" class="min-w-[28rem]"><div class="space-y-5"><div><flux:heading size="lg">Delete {{ $deleteCompanyName }}?</flux:heading><flux:text class="mt-2">The tenant will be removed from active use, while its records are retained.</flux:text></div><flux:error name="deleteCompany"/><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button variant="danger" wire:click="deleteCompany">Delete company</flux:button></div></div></flux:modal>
    </x-pages::settings.layout>
</section>
