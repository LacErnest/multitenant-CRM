import { User } from 'src/app/core/interfaces/user';
import { Company } from 'src/app/shared/interfaces/company';

export interface AuthUserResponse {
  access_token: string;
  expires_in: number;
  message?: string;
  token_type: string;
  user: User;
}

export interface LoginData {
  email: string;
  password: string;
  token?: string;
}

export interface TwoFactorActivateData {
  token: string;
}

export interface TwoFactorNeededResponse {
  message: string;
}

export interface TwoFactorActivatedResponse {
  data: {
    companies: Company[];
  };
  message: string;
}

export interface TwoFactorSecretResponse {
  key: string;
}

export interface LogoutResponse {
  message: string;
}
