import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { QuoteDownPayment } from 'src/app/views/projects/modules/project/enums/quote-down-payment.enum';

interface Entity {
  created_at: string;
  currency_code: number;
  date: string;
  id: string;
  items: EntityItem[];
  legal_country: number;
  legal_entity: string;
  legal_entity_id: string;
  manual_input: boolean;
  price_modifiers: EntityPriceModifier[];
  project_id: string;
  reference: string;
  status: number;
  tax_rate: number;
  total_price: number;
  total_vat: number;
  updated_at: string;
  vat_status: number;
  vat_percentage: number;
}

export interface Quote extends Entity {
  contact_id: string;
  customer_id: string;
  down_payment: QuoteDownPayment;
  expiry_date: string;
  number: string;
  order_id: string;
  reason_of_refusal: string;
  sales_person_id: string[];
  sales_person?: string[];
  xero_id: string;
  shadow: boolean;
  has_media: string;
  second_sales_person_id: string[];
  second_sales_person?: string[];
  down_payment_type: number;
}

export interface Order extends Entity {
  cost: number;
  deadline: string;
  delivered_at: string;
  invoice_id: string;
  markup: number;
  number: string;
  project_manager: string;
  project_manager_id: string;
  quote_id: string;
  total_invoices_price: number;
  potential_markup: number;
  potential_gm: number;
  gross_margin: number;
  potential_cost: number;
  shadow: boolean;
  has_media: string;
  price_user_currency: number;
  master: boolean;
  total_shadows: number;
}

export interface Invoice extends Entity {
  created_by: string;
  currency_rate_customer: number;
  details: any;
  download: boolean;
  due_date: string;
  number: string;
  order: string;
  order_id: string;
  order_legal_entity: string;
  pay_date: string;
  project: string;
  project_id: string;
  purchase_order_id: string;
  resource: string;
  resource_id: string;
  resource_country: number;
  type: number;
  xero_id: string;
  shadow: boolean;
  penalty: number;
  reason_of_penalty: string;
  resource_not_vat_liable: boolean;
  total_paid_amount: number;
  total_paid_amount_usd: number;
  payment_terms: string;
  customer_total_price: number;
  down_payment: number;
  down_payment_status: number;
  eligible_for_earnout: boolean;
  customer_notified_at: string;
  email_template_id: string;
  email_template_globally_disabled: boolean;
  send_client_reminders: boolean;
}

export interface PurchaseOrder extends Entity {
  created_by: string;
  delivery_date: string;
  number: string;
  pay_date: string;
  penalty: any;
  project: string;
  rating: number;
  reason: string;
  reason_of_penalty: string;
  reason_of_rejection: string;
  resource: string;
  resource_country: number;
  resource_currency: number;
  resource_id: string;
  type: number;
  xero_id: string;
  payment_terms: number;
  invoice_authorised: boolean;
  authorised_by: string;
  processed_by: string;
  resource_non_vat_liable: boolean;
  penalty_type: number;
}

export interface Employee extends Entity {
  email: string;
  first_name: string;
  hours: number;
  id: string;
  is_borrowed: boolean;
  last_name: string;
  name: string;
  phone_number: string;
  status: number;
  type: number;
  employee_id: string;
  employee: string;
}

export interface InvoicePayment extends Entity {
  created_by: string;
  invoice_id: string;
  pay_date: string;
  pay_amount: number;
  pay_amount_usd: number;
  currency_code: number;
  status: number;
  id: string;
}
