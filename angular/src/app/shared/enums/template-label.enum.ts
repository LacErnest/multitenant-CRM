import { TemplateType } from 'src/app/shared/types/template-type';

export const enum TemplateLabelEnum {
  QUOTE = 'quote',
  ORDER = 'order',
  INVOICE = 'invoice',
  PURCHASE_ORDER = 'purchase_order',
  NDA = 'NDA',
  CONTRACTOR = 'contractor',
  CUSTOMER = 'customer',
  FREELANCER = 'freelancer',
  EMPLOYEE = 'employee',
}

export function getTemplateLabel(key: TemplateType): string {
  return [
    { key: TemplateLabelEnum.QUOTE, value: 'quote' },
    { key: TemplateLabelEnum.ORDER, value: 'order' },
    { key: TemplateLabelEnum.INVOICE, value: 'invoice' },
    { key: TemplateLabelEnum.PURCHASE_ORDER, value: 'purchase order' },
    { key: TemplateLabelEnum.NDA, value: 'NDA' },
    { key: TemplateLabelEnum.CONTRACTOR, value: 'contractor' },
    { key: TemplateLabelEnum.CUSTOMER, value: 'customer' },
    { key: TemplateLabelEnum.FREELANCER, value: 'freelancer' },
    { key: TemplateLabelEnum.EMPLOYEE, value: 'employee' },
  ].find(t => t.key === key)?.value;
}
