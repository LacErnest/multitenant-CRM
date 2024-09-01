export interface CompanyNotificationSettings {
  company_id: string;
  from_address: string;
  from_name: string;
  invoice_submitted_body: string;
  cc_addresses: string[];
  updated_at: string;
  created_at: string;
}
