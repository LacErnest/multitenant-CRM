export interface EmployeeHistory {
  id: string;
  employee_salary: number;
  working_hours: number;
  start_date: string;
  end_date: string;
  default_currency: number;
}

export interface EmployeeHistoryList {
  data: EmployeeHistory[];
  count: number;
}
