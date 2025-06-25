<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Services\AnalysisService;
use App\Services\CompanyService;
use App\Services\WebsiteParsingService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add Company')]
class Create extends Component
{
    public string $name = '';

    public string $website = '';

    public bool $autoAnalyze = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'website' => [
                'required',
                'url',
                'max:255',
                Rule::unique('companies', 'website'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Company name is required.',
            'website.required' => 'Website URL is required.',
            'website.url' => 'Please enter a valid website URL.',
            'website.unique' => 'This website is already in our database.',
        ];
    }

    public function updatedWebsite(WebsiteParsingService $websiteParsingService): void
    {
        // Auto-populate company name from website if empty
        if (empty($this->name) && ! empty($this->website)) {
            $this->name = $websiteParsingService->extractCompanyNameFromWebsite($this->website);
        }
    }

    public function validateWebsite(): void
    {
        $this->validateOnly('website');
    }

    public function validateName(): void
    {
        $this->validateOnly('name');
    }

    public function save(
        CompanyService $companyService,
        AnalysisService $analysisService
    ): void {
        $this->validate();

        $company = $companyService->createCompany([
            'name' => $this->name,
            'website' => $this->website,
        ]);

        // Start analysis if auto-analyze is enabled
        $analysisService->startAnalysis($company, $this->autoAnalyze);

        if ($this->autoAnalyze) {
            session()->flash('message', 'Company added successfully and analysis started!');
        } else {
            session()->flash('message', 'Company added successfully!');
        }

        $this->redirect(route('companies.show', $company), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.company.create');
    }
}
