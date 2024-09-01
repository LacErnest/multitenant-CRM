export interface SmtpSetting {
  created_at: string;
  id: string;
  smtp_host: string;
  smtp_port: string;
  smtp_encryption: string;
  smtp_username: string;
  smtp_password: string;
  sender_email: string;
  sender_name: string;
  updated_at: string;
  default: boolean;
}
