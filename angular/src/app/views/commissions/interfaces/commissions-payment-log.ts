export interface CommissionsPaymentLogs {
  data: {
    amount: number;
    id: string;
    paid_at: string;
    sales_person_id: string;
    sales_person_name: string;
    status: number;
    showActions: boolean;
  }[];
}

export interface CommissionLog {
  amount: number;
  id: string;
  paid_at: string;
  sales_person_id: string;
  sales_person_name: string;
  status: number;
}

export interface TotalOpenAmount {
  total_commission_amount: number;
}

export interface CreateLog {
  amount: number;
  sales_person_id: string;
}

export interface IndividualCommissionPayment {
  amount: number;
  sales_person_id: string;
  order_id: string;
  invoice_id: string;
  total: number;
}

export interface IndividualCommissionPaymentId {
  sales_person_id: string;
  order_id: string;
  invoice_id: string;
}
