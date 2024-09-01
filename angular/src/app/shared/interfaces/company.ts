import { UserRole } from 'src/app/shared/enums/user-role.enum';

export interface Company {
  currency?: number;
  id: string;
  initials?: string;
  name: string;
  role: UserRole;
  xero_linked?: boolean;
  sales_support_percentage?: number;
}

export interface AllCompanies {
  id: string;
  name: string;
  role: UserRole;
}
