export interface Loan {
  amount: number;
  author: string;
  author_id: string;
  created_at: string;
  deleted_at: string;
  id: string;
  issued_at: string;
  paid_at: string;
  updated_at: string;
  description: string;

  // NOTE: fields for enabling/disabling options in the data-table
  is_edit_allowed?: boolean;
  is_deletion_allowed?: boolean;
}

export interface LoanList {
  data: Loan[];
  count: number;
}
