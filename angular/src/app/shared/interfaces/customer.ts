export interface Customer {
  average_collection_period: number;
  billing_address_id: string;
  billing_addressline_1: string;
  billing_addressline_2: string;
  billing_city: string;
  billing_country: number;
  billing_postal_code: string;
  billing_region: string;
  company: string;
  company_id: string;
  contacts: any[]; // TODO: add interface
  created_at: string;
  default_currency: number;
  description: string;
  email: string;
  id: string;
  industry: number;
  is_same_address: boolean;
  name: string;
  operational_address_id: string;
  operational_addressline_1: string;
  operational_addressline_2: string;
  operational_city: string;
  operational_country: number;
  operational_postal_code: string;
  operational_region: string;
  phone_number: string;
  sales_person: any; // TODO: add interface
  status: number;
  tax_number: string;
  updated_at: string;
  website: string;
  payment_due_date: number;
}
