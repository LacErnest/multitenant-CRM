import { InvoiceStatus } from 'src/app/views/projects/modules/project/enums/invoice-status.enum';

export interface InvoiceStatusUpdate {
  status: InvoiceStatus;
  pay_date?: string;
  email_template_id?: string;
}
