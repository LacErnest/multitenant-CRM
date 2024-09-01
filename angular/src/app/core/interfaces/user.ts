import { Company } from 'src/app/shared/interfaces/company';

export interface User {
  companies: Company[];
  email: string;
  first_name: string;
  google2fa: boolean;
  id: string;
  last_name: string;
  super_user: boolean;
}
