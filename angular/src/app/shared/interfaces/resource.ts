import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { Service, ServiceList } from './service';

export interface Resource {
  addressline_1: string;
  addressline_2: string;
  average_rating: number;
  city: string;
  contract_file: string;
  country: number;
  created_at: number;
  daily_rate: number;
  default_currency: number;
  email: string;
  first_name: string;
  hourly_rate: number;
  id: string;
  legal_entity: string;
  job_title: string;
  last_name: string;
  name: string;
  phone_number: string;
  postal_code: string;
  purchase_orders: PurchaseOrder[];
  region: string;
  status: number;
  tax_number: string;
  type: number;
  updated_at: number;
  services: ServiceList;
  non_vat_liable: boolean;
}
