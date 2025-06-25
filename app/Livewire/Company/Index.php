<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Companies')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'classification')]
    public string $classificationFilter = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public array $selectedCompanies = [];

    public bool $selectAll = false;

    protected string $paginationTheme = 'simple-bootstrap';

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedClassificationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedCompanies = collect($this->companies()->items())->pluck('id')->toArray();
        } else {
            $this->selectedCompanies = [];
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function analyzeSelected(): void
    {
        if (empty($this->selectedCompanies)) {
            $this->addError('selection', 'Please select companies to analyze.');
            return;
        }

        $companies = Company::whereIn('id', $this->selectedCompanies)->get();

        foreach ($companies as $company) {
            // TODO: Dispatch analysis job
            // AnalyzeCompanyJob::dispatch($company);
            $company->update(['status' => CompanyStatus::PENDING]);
        }

        session()->flash('message', count($this->selectedCompanies) . ' companies queued for analysis.');

        $this->selectedCompanies = [];
        $this->selectAll = false;
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedCompanies)) {
            $this->addError('selection', 'Please select companies to delete.');
            return;
        }

        Company::whereIn('id', $this->selectedCompanies)->delete();

        session()->flash('message', count($this->selectedCompanies) . ' companies deleted.');

        $this->selectedCompanies = [];
        $this->selectAll = false;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->classificationFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function companies(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Company::query()
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('website', 'like', '%' . $this->search . '%')
                        ->orWhere('domain', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function (Builder $query) {
                $query->where('status', CompanyStatus::from($this->statusFilter));
            })
            ->when($this->classificationFilter, function (Builder $query) {
                if ($this->classificationFilter === 'unclassified') {
                    $query->whereNull('classification');
                } else {
                    $query->where('classification', CompanyClassification::from($this->classificationFilter));
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return ! empty($this->search) ||
               ! empty($this->statusFilter) ||
               ! empty($this->classificationFilter);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(CompanyStatus::cases())
            ->mapWithKeys(fn(CompanyStatus $status) => [
                $status->value => $status->label()
            ])
            ->toArray();
    }

    #[Computed]
    public function classificationOptions(): array
    {
        $options = collect(CompanyClassification::cases())
            ->mapWithKeys(fn(CompanyClassification $classification) => [
                $classification->value => $classification->label()
            ])
            ->toArray();

        $options['unclassified'] = 'Unclassified';

        return $options;
    }

    public function render(): View
    {
        return view('livewire.company.index');
    }
}
