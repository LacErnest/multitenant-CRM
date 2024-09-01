import { PriceModifierCalculationLogicValue } from 'src/app/shared/enums/price-modifier-calculation-logic-value.enum';
import {
  Invoice,
  PurchaseOrder,
  Quote,
  Employee,
} from 'src/app/shared/interfaces/entities';
import { ProjectCommission } from './project-commissions';
import { ResourceInvoice } from 'src/app/shared/interfaces/resource-invoice';

// TODO: add missing interfaces
export interface Project {
  active_quote: boolean;
  budget: string;
  contact: string;
  contact_id: string;
  created_at: string;
  customer: ProjectCustomer;
  employees: { cols: any; rows: { count: number; data: Employee[] } };
  id: string;
  invoices: { cols: any; rows: { count: number; data: Invoice[] } };
  name: string;
  order: ProjectOrder;
  project_manager: any;
  project_manager_id: string;
  purchase_orders: {
    cols: any;
    rows: { count: number; data: PurchaseOrder[] };
  };
  quotes: { cols: any; rows: { count: number; data: Quote[] } };
  resource_invoices: {
    cols: any;
    rows: { count: number; data: ResourceInvoice[] };
  };
  sales_person?: string;
  sales_person_id?: string[];
  second_sales_person?: string;
  second_sales_person_id?: string[];
  updated_at: string;
  purchase_order_project: boolean;
  resource: ProjectResource;
  price_modifiers_calculation_logic: PriceModifierCalculationLogicValue;
  project_commissions: {
    cols: any;
    rows: { count: number; data: ProjectCommission[] };
  };
}

interface ProjectOrder {
  date: string;
  id: string;
  number: string;
  quote: string;
  quote_id: string;
  status: number;
  shadow: boolean;
  master: boolean;
  intra_company: boolean;
}

interface ProjectCustomer {
  contacts: any[]; // TODO: add interface
  country: number;
  id: string;
  name: string;
  non_vat_liable: boolean;
  legacy_customer: boolean;
  payment_due_date: number;
}

interface ProjectResource {
  country: number;
  id: string;
  name: string;
  non_vat_liable: boolean;
  currency: number;
}
