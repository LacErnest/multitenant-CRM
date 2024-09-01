import { DesignTemplate } from './design-template';

export interface EmailTemplate {
  id: string;
  smtp_setting_id: string;
  title: string;
  cc_addresses: string[];
  reminder_types: number[];
  reminder_values: number[];
  reminder_ids: number[];
  design_template?: DesignTemplate;
  reminder_templates?: string[];
  reminder_design_templates?: DesignTemplate[];
  default: boolean;
  updated_at: string;
  created_at: string;
}
