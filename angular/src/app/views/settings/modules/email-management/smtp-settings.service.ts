import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { SmtpSetting } from './interfaces/smtp-settings';
import { SmtpSettingStatus } from './interfaces/smtp-setting-status';

@Injectable({
  providedIn: 'root',
})
export class SmtpSettingsService {
  public constructor(private http: HttpClient) {}

  public getSmtpSettings(companyId: string): Observable<SmtpSetting[]> {
    return this.http.get<SmtpSetting[]>(`api/${companyId}/settings/smtp`);
  }

  public getSmtpSetting(
    companyId: string,
    id: string
  ): Observable<SmtpSetting> {
    return this.http.get<SmtpSetting>(`api/${companyId}/settings/smtp/${id}`);
  }

  public createSmtpSetting(
    companyId: string,
    settings: SmtpSetting
  ): Observable<SmtpSetting> {
    return this.http.post<SmtpSetting>(
      `api/${companyId}/settings/smtp`,
      settings
    );
  }

  public editSmtpSetting(
    companyId: string,
    id: string,
    settings: SmtpSetting
  ): Observable<SmtpSetting> {
    return this.http.patch<SmtpSetting>(
      `api/${companyId}/settings/smtp/${id}`,
      settings
    );
  }

  public markSmtpSettingAsDefault(
    companyId: string,
    id: string
  ): Observable<SmtpSetting> {
    return this.http.patch<SmtpSetting>(
      `api/${companyId}/settings/smtp/${id}/default`,
      {}
    );
  }

  public deleteSmtpSetting(
    companyId: string,
    id: string
  ): Observable<SmtpSettingStatus> {
    return this.http.delete<SmtpSettingStatus>(
      `api/${companyId}/settings/smtp/${id}`
    );
  }
}
