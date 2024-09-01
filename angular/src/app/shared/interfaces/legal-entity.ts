export interface LegalEntity {
  american_bank: AmericanBank;
  bic: string;
  created_at: string;
  default?: boolean;
  deleted_at: string;
  european_bank: EuropeanBank;
  id: string;
  is_deletion_allowed?: boolean;
  legal_entity_address: Address;
  legal_entity_id?: string;
  name: string;
  swift: string;
  updated_at: string;
  vat_number: string;
  usdc_wallet_address: string;
}

interface AmericanBank {
  account_number: string;
  address_id: string;
  bank_address: Address;
  id: string;
  name: string;
  routing_number: string;
}

interface EuropeanBank {
  address_id: string;
  bank_address: Address;
  bic: string;
  id: string;
  name: string;
  swift: string;
}

interface Address {
  addressline_1: string;
  addressline_2: string;
  city: string;
  country: number;
  postal_code: string;
  region: string;
}

export interface LegalEntitiesList {
  count: number;
  data: LegalEntity[];
}

export interface XeroLinkedResponse {
  is_xero_linked: boolean;
}

export interface CompanyLegalEntitiesList {
  count: number;
  data: CompanyLegalEntity[];
}

export interface CompanyLegalEntity {
  company_id: string;
  country: number;
  created_at: string;
  default: boolean;
  id: number;
  is_deletion_allowed?: boolean;
  legal_entity_id: string;
  name: string;
  updated_at: string;
  local: boolean;
}
