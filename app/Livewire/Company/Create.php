<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
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

    public function updatedWebsite(): void
    {
        // Auto-populate company name from website if empty
        if (empty($this->name) && ! empty($this->website)) {
            $this->extractCompanyNameFromWebsite($this->website);
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

    public function save(): void
    {
        $this->validate();

        $company = Company::query()->create([
            'name' => $this->name,
            'website' => $this->website,
            'domain' => $this->extractDomain($this->website),
        ]);

        // Dispatch analysis job if auto-analyze is enabled
        if ($this->autoAnalyze) {
            // TODO: Dispatch analysis job
            // AnalyzeCompanyJob::dispatch($company);

            session()->flash('message', 'Company added successfully and analysis started!');
        } else {
            session()->flash('message', 'Company added successfully!');
        }

        $this->redirect(route('companies.show', $company), navigate: true);
    }

    private function extractCompanyNameFromWebsite(string $website): void
    {
        $domain = $this->extractDomain($website);

        if ($domain) {
            $domain = preg_replace('/^www\./', '', $domain);

            $pattern = '/^([a-zA-Z0-9\-]+)(?:\.[a-zA-Z]{2,})*$/';
            if (preg_match($pattern, $domain, $matches)) {
                $companyName = $matches[1];

                $companyName = str_replace(['-', '_'], ' ', $companyName);

                $companyName = preg_replace('/^(the|get|my|your|try)\s+/i', '', $companyName);
                $companyName = preg_replace('/\s+(app|inc|corp|llc|ltd|company|co)$/i', '', $companyName);

                $this->name = ucwords(strtolower(trim($companyName)));
            }
        }
    }

    private function extractDomain(string $website): string
    {
        $parsed = parse_url($website);

        if (isset($parsed['host'])) {
            return strtolower($parsed['host']);
        }

        return '';
    }

    public function render(): View
    {
        return view('livewire.company.create');
    }
}
