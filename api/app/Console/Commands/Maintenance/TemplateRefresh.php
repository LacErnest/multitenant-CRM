<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\TemplateType;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TemplateRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:docx_template_refresh {name?} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh company template docs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('Starting refreshing projects and employees costs.');
        $companies = Company::all();

        if ($this->option('all')) {
            $templates = TemplateType::getAll();
        } else {
            $template_name = $this->argument('name');
            if (!in_array($template_name, TemplateType::getValues())) {
                $this->line('No template found with that name.');
                return 0;
            }
            $template = TemplateType::make($template_name);
            $templates = collect([$template]);
        }
        try {
            foreach ($companies as $company) {
                $this->line('Refreshing docx templates of tenant ' . $company->name . '.');
                foreach ($templates as $template) {
                    $this->moveDocxFiles($company, $template, $this->option('force'));
                }
            }
        } catch (\Throwable $th) {
            $this->line('Oeps, something went wrong. Sorry');
            $this->line('Rolling back to previous database state');
            throw $th;
        }
        $this->line('Success!!!');

        return 0;
    }

    // Fonction pour déplacer les fichiers .docx
    private function moveDocxFiles(Company $company, TemplateType $templateType, bool $force = false)
    {
        try {
            $disk = Storage::disk('templates');
            // New folder path with company id
            $destinationPath = $company->id . '/' . $templateType->getIndex();

            // Create new folder if none exists
            Storage::makeDirectory($destinationPath);

            $templateFileName = $templateType->getValue() . '.docx';

            if ($disk->exists($templateFileName)) {
                $destinationFile = $destinationPath . '/' . basename($templateFileName);

                if ($disk->exists($destinationFile)) {
                    // The target file exists, delete it
                    $disk->delete($destinationFile);
                }

                $disk->copy($templateFileName, $destinationFile);
            }
        } catch (\Throwable $th) {
            if (!$force) {
                throw $th;
            }
        }
    }
}
