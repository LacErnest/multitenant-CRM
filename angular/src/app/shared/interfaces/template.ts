export interface Template {
  fields: TemplateField[];
  link: string;
  name: string;
}

export interface ContractTemplates {
  NDA: Template;
  contractor: Template;
  customer: Template;
  employee: Template;
  freelancer: Template;
}

export interface CompanyTemplates {
  invoice: Template;
  order: Template;
  purchase_order: Template;
  quote: Template;
}

export interface TemplateField {
  value: string;
  description: string;
}
