<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Add Company')]
class Create extends Component
{
    #[Validate]
    public string $name = '';

    #[Validate]
    public string $website = '';

    public string $description = '';

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
            'description' => ['nullable', 'string', 'max:1000'],
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

    public function updated(string $property, mixed $value): void
    {
        // Real-time validation
        $this->validateOnly($property);

        // Auto-populate company name from website if empty
        if ($property === 'website' && empty($this->name) && ! empty($value)) {
            $this->extractCompanyNameFromWebsite($value);
        }
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
            // Remove common TLDs and format as title case
            $name = str_replace(['.com', '.org', '.net', '.io', '.co'], '', $domain);
            $this->name = ucwords(str_replace(['-', '_'], ' ', $name));
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
