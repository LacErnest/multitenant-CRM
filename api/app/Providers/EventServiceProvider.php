<?php

namespace App\Providers;

use App\Events\EmployeeSalariesUpdatedEvent;
use App\Events\InvoiceSubmitted;
use App\Events\InvoiceSubmittedSuccessfully;
use App\Listeners\ConfigureCreatingTenantEvent;
use App\Listeners\ConfigureDeletingTenantEvent;
use App\Listeners\ConfigureTenantCache;
use App\Listeners\ConfigureTenantConnection;
use App\Listeners\ConfigureTenantDatabase;
use App\Listeners\ConfigureTenantDatabaseMutations;
use App\Listeners\ConfigureTenantMigrations;
use App\Listeners\ConfigureTenantSeeds;
use App\Listeners\ConfigureUpdatingTenantEvent;
use App\Listeners\EmailSentListener;
use App\Listeners\EmployeeSalariesUpdatedListener;
use App\Listeners\InvoiceSubmittedListener;
use App\Listeners\InvoiceSubmittedSuccessfullyListener;
use App\Listeners\ResolveTenantConnection;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\EmployeeHistory;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Order;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Observers\CustomerObserver;
use App\Observers\InvoiceObserver;
use App\Models\User;
use App\Observers\ContactObserver;
use App\Observers\EmployeeHistoryObserver;
use App\Observers\ItemObserver;
use App\Observers\OrderObserver;
use App\Observers\ProjectObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\QuoteObserver;
use App\Observers\EmployeeObserver;
use App\Observers\ResourceObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Tenancy\Affects\Cache\Events\ConfigureCache;
use Tenancy\Affects\Connections\Events\Drivers\Configuring;
use Tenancy\Affects\Connections\Events\Resolving;
use Tenancy\Hooks\Database\Events\Drivers\Creating;
use Tenancy\Hooks\Database\Events\Drivers\Deleting;
use Tenancy\Hooks\Database\Events\Drivers\Updating;
use Tenancy\Hooks\Migration\Events\ConfigureMigrations;
use  Tenancy\Hooks\Migration\Events\ConfigureSeeds;
use Tenancy\Hooks\Database\Events\ConfigureDatabaseMutation;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ConfigureCache::class => [
            ConfigureTenantCache::class
        ],
        Configuring::class => [
            ConfigureTenantConnection::class
        ],
        \Tenancy\Hooks\Database\Events\Drivers\Configuring::class => [
            ConfigureTenantDatabase::class,
        ],
        Resolving::class => [
            ResolveTenantConnection::class
        ],
        ConfigureMigrations::class => [
            ConfigureTenantMigrations::class
        ],
        ConfigureSeeds::class => [
            ConfigureTenantSeeds::class
        ],
        Creating::class => [
            ConfigureCreatingTenantEvent::class
        ],
        Updating::class => [
            ConfigureUpdatingTenantEvent::class
        ],
        Deleting::class => [
            ConfigureDeletingTenantEvent::class
        ],
        ConfigureDatabaseMutation::class => [
            ConfigureTenantDatabaseMutations::class
        ],
        EmployeeSalariesUpdatedEvent::class => [
            EmployeeSalariesUpdatedListener::class
        ],
        InvoiceSubmitted::class => [
            InvoiceSubmittedListener::class,
        ],
        MessageSent::class => [
            EmailSentListener::class,
        ],
        InvoiceSubmittedSuccessfully::class => [
            InvoiceSubmittedSuccessfullyListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Item::observe(ItemObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        Quote::observe(QuoteObserver::class);

        Invoice::observe(InvoiceObserver::class);
        Customer::observe(CustomerObserver::class);
        Resource::observe(ResourceObserver::class);

        User::observe(UserObserver::class);
        Employee::observe(EmployeeObserver::class);
        Contact::observe(ContactObserver::class);

        Order::observe(OrderObserver::class);
        Project::observe(ProjectObserver::class);

        EmployeeHistory::observe(EmployeeHistoryObserver::class);
    }
}
