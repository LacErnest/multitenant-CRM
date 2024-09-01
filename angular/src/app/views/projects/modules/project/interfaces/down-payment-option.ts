import { QuoteDownPayment } from 'src/app/views/projects/modules/project/enums/quote-down-payment.enum';

export interface DownPaymentOption {
  label: string;
  value: number | QuoteDownPayment;
}
