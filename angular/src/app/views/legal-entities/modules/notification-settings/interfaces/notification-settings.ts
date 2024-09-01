export interface LegalEntityNotificationSettings {
  created_at: string;
  legal_entity_id: string;
  enable_submited_invoice_notification: boolean;
  notification_contacts: string;
  notification_footer: string;
  notification_contacts_names?: string[];
  updated_at: string;
}
