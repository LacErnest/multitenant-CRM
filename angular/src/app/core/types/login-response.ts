import {
  TwoFactorNeededResponse,
  AuthUserResponse,
} from 'src/app/core/interfaces/authorization';

export type LoginResponse = AuthUserResponse | TwoFactorNeededResponse;
