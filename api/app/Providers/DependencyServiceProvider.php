<?php

namespace App\Providers;

use App\Contracts\Repositories\CompanyRentRepositoryInterface;
use App\Contracts\Repositories\BankRepositoryInterface;
use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CompanySettingRepositoryInterface;
use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Contracts\Repositories\CompanyLoanRepositoryInterface;
use App\Contracts\Repositories\CompanyNotificationSettingRepositoryInterface;
use App\Contracts\Repositories\SmtpSettingRepositoryInterface;
use App\Contracts\Repositories\CustomerAddressRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\DesignTemplateRepositoryInterface;
use App\Contracts\Repositories\EarnOutStatusRepositoryInterface;
use App\Contracts\Repositories\EmailTemplateRepositoryInterface;
use App\Contracts\Repositories\EmployeeHistoryRepositoryInterface;
use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\LegalEntityXeroConfigRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Contracts\Repositories\InvoicePaymentRepositoryInterface;
use App\Contracts\Repositories\LegalEntityNotificationSettingRepositoryInterface;
use App\Contracts\Repositories\LegalEntityTemplateRepositoryInterface;
use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Contracts\Repositories\QuoteRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Contracts\Repositories\TaxRateRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\XeroEntityStorageRepositoryInterface;
use App\Repositories\Eloquent\EloquentBankRepository;
use App\Repositories\Eloquent\EloquentCommentRepository;
use App\Repositories\Eloquent\EloquentCompanyLoanRepository;
use App\Repositories\Eloquent\EloquentCompanyNotificationSettingRepository;
use App\Repositories\Eloquent\EloquentSmtpSettingRepository;
use App\Repositories\Eloquent\EloquentCompanyRentRepository;
use App\Repositories\Eloquent\EloquentCompanyRepository;
use App\Repositories\Eloquent\EloquentCompanySettingRepository;
use App\Repositories\Eloquent\EloquentContactRepository;
use App\Repositories\Eloquent\EloquentCustomerAddressRepository;
use App\Repositories\Eloquent\EloquentCustomerRepository;
use App\Repositories\Eloquent\EloquentDesignTemplateRepository;
use App\Repositories\Eloquent\EloquentEarnOutStatusRepository;
use App\Repositories\Eloquent\EloquentEmailTemplateRepository;
use App\Repositories\Eloquent\EloquentEmployeeHistoryRepository;
use App\Repositories\Eloquent\EloquentGlobalTaxRateRepository;
use App\Repositories\Eloquent\EloquentInvoiceRepository;
use App\Repositories\Eloquent\EloquentLegalEntityRepository;
use App\Repositories\Eloquent\EloquentLegalEntitySettingRepository;
use App\Repositories\Eloquent\EloquentLegalEntityXeroConfigRepository;
use App\Repositories\Eloquent\EloquentOrderRepository;
use App\Repositories\Eloquent\EloquentProjectRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentInvoicePaymentRepository;
use App\Repositories\Eloquent\EloquentLegalEntityNotificationSettingRepository;
use App\Repositories\Eloquent\EloquentLegalEntityTemplateRepository;
use App\Repositories\Eloquent\EloquentPurchaseOrderRepository;
use App\Repositories\Eloquent\EloquentQuoteRepository;
use App\Repositories\Eloquent\EloquentResourceRepository;
use App\Repositories\Eloquent\EloquentSettingRepository;
use App\Repositories\Eloquent\EloquentTaxRateRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentXeroEntityStorageRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class DependencyServiceProvider
 *
 * Registers all dependencies that requires configurable resolve strategy
 */
class DependencyServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        // User
        UserRepositoryInterface::class                  => EloquentUserRepository::class,

        // Contact
        ContactRepositoryInterface::class               => EloquentContactRepository::class,

        //CompanyLoan
        CompanyLoanRepositoryInterface::class           => EloquentCompanyLoanRepository::class,

        //TaxRate
        TaxRateRepositoryInterface::class               => EloquentTaxRateRepository::class,

        //Order
        OrderRepositoryInterface::class                 => EloquentOrderRepository::class,

        //Project
        ProjectRepositoryInterface::class               => EloquentProjectRepository::class,

        //Employee
        EmployeeRepositoryInterface::class              => EloquentEmployeeRepository::class,

        //Resource
        ResourceRepositoryInterface::class              => EloquentResourceRepository::class,

        //PurchaseOrder
        PurchaseOrderRepositoryInterface::class         => EloquentPurchaseOrderRepository::class,

        //CompanyRent
        CompanyRentRepositoryInterface::class           => EloquentCompanyRentRepository::class,

        //LegalEntity
        LegalEntityRepositoryInterface::class           => EloquentLegalEntityRepository::class,

        //CustomerAddress
        CustomerAddressRepositoryInterface::class       => EloquentCustomerAddressRepository::class,

        //Company
        CompanyRepositoryInterface::class               => EloquentCompanyRepository::class,

        //CompanySetting
        CompanySettingRepositoryInterface::class        => EloquentCompanySettingRepository::class,

        //GlobalTaxRate
        GlobalTaxRateRepositoryInterface::class         => EloquentGlobalTaxRateRepository::class,

        //LegalEntityXeroConfig
        LegalEntityXeroConfigRepositoryInterface::class => EloquentLegalEntityXeroConfigRepository::class,

        //Setting
        SettingRepositoryInterface::class               => EloquentSettingRepository::class,

        //LegalEntitySetting
        LegalEntitySettingRepositoryInterface::class    => EloquentLegalEntitySettingRepository::class,

        //LegalEntityNotificationSetting
        LegalEntityNotificationSettingRepositoryInterface::class    => EloquentLegalEntityNotificationSettingRepository::class,

        //CompanyNotificationSetting
        CompanyNotificationSettingRepositoryInterface::class    => EloquentCompanyNotificationSettingRepository::class,

        //SmtpSetting
        SmtpSettingRepositoryInterface::class    => EloquentSmtpSettingRepository::class,

        //Quote
        QuoteRepositoryInterface::class                 => EloquentQuoteRepository::class,

        //Invoice
        InvoiceRepositoryInterface::class               => EloquentInvoiceRepository::class,

        //InvoicePayment
        InvoicePaymentRepositoryInterface::class        => EloquentInvoicePaymentRepository::class,

        //Customer
        CustomerRepositoryInterface::class              => EloquentCustomerRepository::class,

        //XeroEntityStorage
        XeroEntityStorageRepositoryInterface::class     => EloquentXeroEntityStorageRepository::class,

        //Bank
        BankRepositoryInterface::class                  => EloquentBankRepository::class,

        //EarnOutStatus
        EarnOutStatusRepositoryInterface::class         => EloquentEarnOutStatusRepository::class,

        //EmployeeHistory
        EmployeeHistoryRepositoryInterface::class       => EloquentEmployeeHistoryRepository::class,

        //Comment
        CommentRepositoryInterface::class               => EloquentCommentRepository::class,

        //EmailTemplate
        EmailTemplateRepositoryInterface::class         => EloquentEmailTemplateRepository::class,

        //DesignTemplate
        DesignTemplateRepositoryInterface::class         => EloquentDesignTemplateRepository::class,
    ];
}
