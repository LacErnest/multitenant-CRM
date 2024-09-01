import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { CompanyNotificationSettings } from './modules/company-legal-entities/interfaces/company-notification-settings';

@Injectable({
  providedIn: 'root',
})
export class CompanyNotificationSettingsService {
  public constructor(private http: HttpClient) {}

  public getSettings(
    companyId: string
  ): Observable<CompanyNotificationSettings> {
    return this.http.get<CompanyNotificationSettings>(
      `api/${companyId}/settings/notifications`
    );
  }

  public createSettings(
    companyId: string,
    settings: CompanyNotificationSettings
  ): Observable<CompanyNotificationSettings> {
    return this.http.post<CompanyNotificationSettings>(
      `api/${companyId}/settings/notifications`,
      settings
    );
  }

  public editSettings(
    companyId: string,
    settings: CompanyNotificationSettings
  ): Observable<CompanyNotificationSettings> {
    return this.http.patch<CompanyNotificationSettings>(
      `api/${companyId}/settings/notifications`,
      settings
    );
  }
}
