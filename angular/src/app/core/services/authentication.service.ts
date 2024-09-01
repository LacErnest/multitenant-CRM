import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import {
  LoginData,
  LogoutResponse,
  TwoFactorActivateData,
  TwoFactorActivatedResponse,
  TwoFactorSecretResponse,
} from 'src/app/core/interfaces/authorization';
import { User } from 'src/app/core/interfaces/user';
import { GlobalService } from 'src/app/core/services/global.service';
import { LoginResponse } from 'src/app/core/types/login-response';
import { UserRole } from 'src/app/shared/enums/user-role.enum';

@Injectable({
  providedIn: 'root',
})
export class AuthenticationService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public login(credentials: LoginData): Observable<LoginResponse> {
    return this.http.post<LoginResponse>('api/auth/login', credentials);
  }

  // TODO: add interfaces
  public recoverPassword(email: string): Observable<any> {
    return this.http.post('api/auth/password/recover', email);
  }

  public resetPassword(params: HttpParams, password: string): Observable<any> {
    return this.http.post('api/auth/password/reset', password, {
      params: params,
    });
  }

  public setPassword(params: HttpParams, password: string): Observable<any> {
    return this.http.post('api/auth/password/set', password, {
      params: params,
    });
  }

  public logout(): Observable<LogoutResponse> {
    return this.http.post<LogoutResponse>('api/auth/logout', {});
  }

  public get2FASecret(): Observable<TwoFactorSecretResponse> {
    return this.http.get<TwoFactorSecretResponse>('api/profile/2fa');
  }

  public activate2FA(
    token: TwoFactorActivateData
  ): Observable<TwoFactorActivatedResponse> {
    return this.http.put<TwoFactorActivatedResponse>('api/profile/2fa', token);
  }

  public setCompanies(user: User): void {
    const companies = user.companies;
    const [firstCompany] = companies;

    if (firstCompany.role === UserRole.ADMINISTRATOR) {
      const allCompany = { name: 'All', id: 'all', role: 0 };
      companies.push(allCompany);
      this.globalService.currentCompany = allCompany;
    } else {
      this.globalService.currentCompany = firstCompany;
    }

    localStorage.setItem('companies', JSON.stringify(companies));
    this.globalService.companies = companies;
  }
}
