import { DownPaymentOption } from 'src/app/views/projects/modules/project/interfaces/down-payment-option';

export enum QuoteDownPayment {
  PERCENTAGE,
  FIXED,
}

export function getQuoteDownPaymentOptions(): DownPaymentOption[] {
  return [
    { label: 'percentage', value: QuoteDownPayment.PERCENTAGE },
    { label: 'fixed', value: QuoteDownPayment.FIXED },
  ];
}
