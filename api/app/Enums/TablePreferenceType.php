<?php


namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self users()
 * @method static self customers()
 * @method static self projects()
 * @method static self resources()
 * @method static self quotes()
 * @method static self orders()
 * @method static self invoices()
 * @method static self purchase_orders()
 * @method static self project_employees()
 * @method static self project_quotes()
 * @method static self project_orders()
 * @method static self project_invoices()
 * @method static self project_purchase_orders()
 * @method static self employees()
 * @method static self services()
 * @method static self contacts()
 * @method static self project_resource_invoices()
 * @method static self resource_invoices()
 * @method static self external_access_purchase_orders()
 * @method static self resource_services()
 * @method static self legal_entities()
 * @method static self employee_histories()
 * @method static self invoice_payments()
 * @method static self comments()
 * @method static self company_rents()
 * @method static self smtp_settings()
 * @method static self email_templates()
 * @method static self project_sales_commission_percentages()
 * @method static self design_templates()
 *
 *
 * @method static bool isUsers(int|string $value = null)
 * @method static bool isCustomers(int|string $value = null)
 * @method static bool isProjects(int|string $value = null)
 * @method static bool isResources(int|string $value = null)
 * @method static bool isQuotes(int|string $value = null)
 * @method static bool isOrders(int|string $value = null)
 * @method static bool isInvoice_payments(int|string $value = null)
 * @method static bool isPurchase_orders(int|string $value = null)
 * @method static bool isProject_employees(int|string $value = null)
 * @method static bool isProject_quotes(int|string $value = null)
 * @method static bool isProject_orders(int|string $value = null)
 * @method static bool isProject_invoices(int|string $value = null)
 * @method static bool isProject_purchase_orders(int|string $value = null)
 * @method static bool isEmployees(int|string $value = null)
 * @method static bool isServices(int|string $value = null)
 * @method static bool isContacts(int|string $value = null)
 * @method static bool isProject_resource_invoices(int|string $value = null)
 * @method static bool isResource_invoices(int|string $value = null)
 * @method static bool isExternal_access_purchase_orders(int|string $value = null)
 * @method static bool isResource_services(int|string $value = null)
 * @method static bool isLegal_entities(int|string $value = null)
 * @method static bool isEmployeeHistories(int|string $value = null)
 * @method static bool isInvoices(int|string $value = null)
 * @method static bool isInvoice_payments(int|string $value = null)
 * @method static bool isComment(int|string $value = null)
 * @method static bool isCompanyRents(int|string $value = null)
 * @method static bool isSmtp_setting(int|string $value = null)
 * @method static bool isEmail_template(int|string $value = null)
 * @method static bool isProjectSalesCommissionPercentages(int|string $value = null)
 * @method static bool isDesignTemplates(int|string $value = null)
 */
final class TablePreferenceType extends Enum
{
    const MAP_INDEX = [
        'users' => 0,
        'customers' => 1,
        'projects' => 2,
        'resources' => 3,
        'quotes' => 4,
        'orders' => 5,
        'invoices' => 6,
        'purchase_orders' => 7,
        'project_employees' => 8,
        'project_quotes' => 9,
        'project_orders' => 10,
        'project_invoices' => 11,
        'project_purchase_orders' => 12,
        'employees' => 13,
        'services' => 14,
        'contacts' => 15,
        'project_resource_invoices' => 16,
        'resource_invoices' => 17,
        'external_access_purchase_orders' => 18,
        'resource_services' => 19,
        'legal_entities' => 20,
        'employee_histories' => 21,
        'invoice_payments' => 22,
        'comments' => 23,
        'company_rents' => 24,
        'smtp_settings' => 25,
        'email_templates' => 26,
        'project_sales_commission_percentages' => 27,
        'design_templates' => 28,
    ];

    const MAP_VALUE = [
        'users' => 'Users',
        'customers' => 'Customers',
        'projects' => 'Projects',
        'resources' => 'Resources',
        'quotes' => 'Quotes',
        'orders' => 'Orders',
        'invoices' => 'Invoices',
        'purchase_orders' => 'Purchase Orders',
        'project_employees' => 'Project Employees',
        'project_quotes' => 'Project Quotes',
        'project_orders' => 'Project Orders',
        'project_invoices' => 'Project Invoices',
        'project_purchase_orders' => 'Project Purchase Orders',
        'employees' => 'Employees',
        'services' => 'Services',
        'contacts' => 'contacts',
        'project_resource_invoices' => 'Project Resource Invoices',
        'resource_invoices' => 'Resource Invoices',
        'external_access_purchase_orders' => 'External Accessible Purchase Orders',
        'resource_services' => 'Resource Services',
        'legal_entities' => 'Legal Entities',
        'employee_histories' => 'Employee Histories',
        'invoice_payments' => 'Invoices Payments',
        'comments' => 'Comment',
        'company_rents' => 'CompanyRent',
        'smtp_settings' => 'Email Configurations',
        'email_templates' => 'Email Templates',
        'project_sales_commission_percentages' => 'Project Sales Commission Percentages',
        'design_templates' => 'Design Templates',
    ];
}
