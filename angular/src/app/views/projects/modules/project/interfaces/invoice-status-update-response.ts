import { Invoice } from 'src/app/shared/interfaces/entities';

export interface InvoiceStatusUpdateResponse {
  status: 'error' | 'success';
  message?: string;
  invoice: Invoice;
}
