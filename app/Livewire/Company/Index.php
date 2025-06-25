<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Services\CompanyListService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function analyzeSelected(CompanyListService $companyListService): void
    {
        if (empty($this->selectedCompanies)) {
            $this->addError('selection', 'Please select companies to analyze.');

            return;
        }

        $processedCount = $companyListService->analyzeSelectedCompanies($this->selectedCompanies);

        session()->flash('message', $processedCount.' companies queued for analysis.');

        $this->selectedCompanies = [];
        $this->selectAll = false;
    }

    public function deleteSelected(CompanyListService $companyListService): void
    {
        if (empty($this->selectedCompanies)) {
            $this->addError('selection', 'Please select companies to delete.');

            return;
        }

        $deletedCount = $companyListService->deleteSelectedCompanies($this->selectedCompanies);

        session()->flash('message', $deletedCount.' companies deleted.');

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
    public function companies(): LengthAwarePaginator
    {
        return app(CompanyListService::class)->getCompanies(
            $this->search,
            $this->statusFilter,
            $this->classificationFilter,
            $this->sortBy,
            $this->sortDirection,
            $this->perPage
        );
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
        return app(CompanyListService::class)->getStatusOptions();
    }

    #[Computed]
    public function classificationOptions(): array
    {
        return app(CompanyListService::class)->getClassificationOptions();
    }

    #[Computed]
    public function sortableColumns(): array
    {
        return app(CompanyListService::class)->getSortableColumns();
    }

    #[Computed]
    public function filterSummary(): array
    {
        return app(CompanyListService::class)->getFilterSummary(
            $this->search,
            $this->statusFilter,
            $this->classificationFilter
        );
    }

    public function render(): View
    {
        return view('livewire.company.index');
    }
}
