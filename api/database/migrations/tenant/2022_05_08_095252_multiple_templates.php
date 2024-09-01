<?php

use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Enums\TemplateType;
use App\Models\Template;
use App\Repositories\TemplateRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MultipleTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $template = Template::create([
            'id'                           => Str::uuid(),
            'name'                         => 'Default',
        ]);

        (new TemplateRepository($template))->addDefaultTemplates($template);

        $settingRepository = App::make(SettingRepositoryInterface::class);

        $settings = $settingRepository->getAll();

        if ($settings->isNotEmpty()) {
            $setting = $settings->first();

            foreach (TemplateType::getValues() as $value) {
                if ($setting->getMedia('templates_' . $value)->count()) {
                    $oldTemplate = $setting->getFirstMediaPath('templates_' . $value);
                    $template->addMedia($oldTemplate)
                        ->setFileName($value . '.docx')
                        ->setName($value)
                        ->toMediaCollection('templates_' . $value)
                        ->save();
                    $setting->getFirstMedia('templates_' . $value)->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
