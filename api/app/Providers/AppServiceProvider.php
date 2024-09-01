<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tenancy\Identification\Contracts\ResolvesTenants;
use App\Services\Formaters\ExtendedCurrencyFormatter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Swift_Mailer;
use Swift_SmtpTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving(ResolvesTenants::class, function (ResolvesTenants $resolver) {
            $resolver->addModel(Company::class);

            return $resolver;
        });

        $this->app->singleton('extendedCurrencyFormatter', function ($app) {
            return new ExtendedCurrencyFormatter();
        });
        $this->registerCompanyMailer();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->addDropUniqueIfExistsMacro();

        $headerParser = new AuthHeaders;
        $headerParser->setHeaderName('X-Authorization');
        $this->app['tymon.jwt.parser']->setChain([$headerParser]);

        JsonResource::withoutWrapping();
        $this->app->register(\Illuminate\Mail\MailServiceProvider::class);
    }

    private function registerCompanyMailer()
    {
        $this->app->bind('company.mailer', function ($app, $parameters) {
            $mailer = $this->createMailerInstance($parameters);
            $this->configureMailer($mailer, $parameters);
            return $mailer;
        });
    }

    private function createMailerInstance($parameters)
    {
        $transport = (new Swift_SmtpTransport($parameters['smtp_host'], $parameters['smtp_port']))
            ->setUsername($parameters['smtp_username'])
            ->setPassword(Crypt::decryptString($parameters['smtp_password']))
            ->setEncryption($parameters['smtp_encryption']);

        $swiftMailer = new Swift_Mailer($transport);
        return new Mailer('company.mailer', $this->app->get('view'), $swiftMailer, $this->app->get('events'));
    }

    private function configureMailer(Mailer $mailer, $parameters)
    {
        $mailer->alwaysReplyTo($parameters['sender_email'], $parameters['sender_name']);
    }

    private function addDropUniqueIfExistsMacro(): void
    {
        Blueprint::macro('dropUniqueIfExists', function ($index) {
            $table = $this->getTable();
            $constraintExists = DB::table('information_schema.table_constraints')
                ->where('table_schema', env('DB_DATABASE'))
                ->where('table_name', $table)
                ->where('constraint_name', $index)
                ->where('constraint_type', 'UNIQUE')
                ->exists();

            if ($constraintExists) {
                $this->dropUnique($index);
            }
        });
    }
}
