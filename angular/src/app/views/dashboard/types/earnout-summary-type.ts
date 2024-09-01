export type SummaryType = {
  earnout_bonus: number;
  earnout_percentage: number;
  gross_margin: number;
  gross_margin_bonus: number;
  gross_margin_percentage: number;
  gross_margin_ratio: number;
  total_bonus: number;
  salaries: {
    items: {
      id: string;
      name: string;
      start_of_employment: string;
      end_of_employment: string;
      salary_per_month: number;
      salary_for_this_quarter: number;
    }[];
    total_internal_salary: number;
  };
  total_costs: number;
  total_external_employee_costs: number;
  total_legacy_amount: number;
  total_revenue: number;
  rents: {
    items: {
      id: string;
      name: string;
      start_of_rent: string;
      end_of_rent: string;
      cost_per_month: number;
      cost_for_this_quarter: number;
    }[];
    total_rent_costs: number;
  };
  loans: {
    loans: {
      id: string;
      description: string;
      amount: number;
      issued_at: string;
      paid_at: string;
      already_paid: number;
    }[];
    open_loan_amount_before_quarter: number;
    amount_of_loans_this_quarter: number;
    amount_paid_this_quarter: number;
    loan_amount_still_to_pay: number;
  };
  orders_per_customer: {
    items: {
      id: string;
      name: string;
      costs: number;
      external_employee_costs: number;
      legacy_amount: number;
      revenue: number;
      project_id: string;
      delivered: boolean;
    }[];
    name: string;
    id: number;
    expanded: boolean;
    total_costs: number;
    total_external_employee_costs: number;
    total_legacy_amount: number;
    total_revenue: number;
    all_delivered: boolean;
  }[];
  orders_per_legacy_customer: {
    items: {
      id: string;
      name: string;
      legacy_bonus: number;
      legacy_revenue: number;
      project_id: string;
      delivered: boolean;
    }[];
    id: string;
    name: string;
    expanded: boolean;
    total_legacy_bonus: number;
    total_legacy_revenue: number;
    all_delivered: boolean;
  }[];
  purchase_orders_without_orders: {
    items: {
      id: string;
      project_id: string;
      number: string;
      amount: number;
      authorised_at: string;
      paid_at: string;
    }[];
    resource: string;
    resource_id: string;
    expanded: boolean;
    total_amount: number;
  }[];
};

export type StatusType = {
  message: string | null;
  approved: string | null;
  confirmed: string | null;
  id: string;
  quarter: string;
  received: string | null;
};

export type ActionType = {
  approve: string;
  confirm: string;
  received: string;
};

export type PredictionType = {
  months: {
    revenue: number;
    possible_revenue: number;
    po_costs: number;
    possible_po_costs: number;
    external_salary_costs: number;
    possible_external_salary_costs: number;
    monthly_costs: number;
    possible_monthly_costs: number;
    salaries: number;
    possible_salaries: number;
    total_actual: number;
    total_possible: number;
    monthly_total: number;
    legacy_actual: number;
    legacy_possible: number;
    total_legacy: number;
    name: string;
  }[];
  total_gm: number;
  total_legacy: number;
  total_gm_bonus: number;
  total_legacy_bonus: number;
  total_bonus: number;
  just_acquired: boolean;
};
