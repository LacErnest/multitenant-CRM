import { PurchaseOrder } from '../../../shared/interfaces/entities';

export interface Employee {
  addressline_1: string;
  addressline_2: string;
  city: string;
  country: string;
  created_at: string;
  email: string;
  facebook_profile: string;
  first_name: string;
  hourly_rate: string;
  id?: string;
  last_name: string;
  legal_country: number;
  legal_entity: string;
  legal_entity_id: string;
  linked_in_profile: string;
  phone_number: string;
  postal_code: string;
  region: string;
  role: string;
  salary: string;
  started_at: string;
  status: number;
  is_pm: number;
  type: number;
  updated_at: string;
  working_hours: string;
  overhead_employee: boolean;
  purchase_orders: PurchaseOrder[];
  default_currency: number;
  files: EmployeeFiles[];
}

export interface EmployeeFiles {
  id: string;
  file_name: string;
}
