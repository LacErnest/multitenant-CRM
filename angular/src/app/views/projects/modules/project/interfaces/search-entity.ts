import { Contact } from 'src/app/shared/interfaces/contact';

export interface SearchEntity {
  id: string;
  name: string;
  status?: string;
}

export interface ContactSearchEntity {
  key: string;
  value: string;
}

export interface ResourceSearchEntity extends SearchEntity {
  country: number;
  default_currency: number;
  non_vat_liable: boolean;
}

export interface CustomerSearchEntity extends SearchEntity {
  billing_country: number;
  contacts: Contact[];
  default_currency: number;
  sales_person: string;
  sales_person_id: string;
  primary_contact_id: string;
  primary_contact: string;
  non_vat_liable: boolean;
  legacy_customer: boolean;
}
