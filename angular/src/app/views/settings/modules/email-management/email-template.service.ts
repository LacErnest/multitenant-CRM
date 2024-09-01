import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { EmailTemplate } from './interfaces/email-template';
import { EmailTemplateStatus } from './interfaces/email-template-status';

@Injectable({
  providedIn: 'root',
})
export class EmailTemplateService {
  public constructor(private http: HttpClient) {}

  public getEmailTemplates(companyId: string): Observable<EmailTemplate[]> {
    return this.http.get<EmailTemplate[]>(
      `api/${companyId}/settings/email-templates`
    );
  }

  public getEmailTemplate(
    companyId: string,
    id: string
  ): Observable<EmailTemplate> {
    return this.http.get<EmailTemplate>(
      `api/${companyId}/settings/email-templates/${id}`
    );
  }

  public getEmailTemplateContent(
    companyId: string,
    id: string
  ): Observable<{ html: string }> {
    return this.http.get<{ html: string }>(
      `api/${companyId}/settings/email-templates/${id}/html`
    );
  }

  public createEmailTemplate(
    companyId: string,
    emailTemplate: EmailTemplate
  ): Observable<EmailTemplate> {
    return this.http.post<EmailTemplate>(
      `api/${companyId}/settings/email-templates`,
      emailTemplate
    );
  }

  public editEmailTemplate(
    companyId: string,
    id: string,
    emailTemplate: EmailTemplate
  ): Observable<EmailTemplate> {
    return this.http.patch<EmailTemplate>(
      `api/${companyId}/settings/email-templates/${id}`,
      emailTemplate
    );
  }

  public deleteEmailTemplate(
    companyId: string,
    id: string
  ): Observable<EmailTemplateStatus> {
    return this.http.delete<EmailTemplateStatus>(
      `api/${companyId}/settings/email-templates/${id}`
    );
  }

  public markEmailTemplateAsDefault(
    companyId: string,
    id: string
  ): Observable<EmailTemplate> {
    return this.http.patch<EmailTemplate>(
      `api/${companyId}/settings/email-templates/${id}/default`,
      {}
    );
  }

  public toggleGloballyDisabledStatus(companyId: string): Observable<boolean> {
    return this.http.patch<boolean>(
      `api/${companyId}/settings/email-templates/toggle/status`,
      {}
    );
  }

  public getGloballyDisabledStatus(companyId: string): Observable<boolean> {
    return this.http.get<boolean>(
      `api/${companyId}/settings/email-templates/get/status`,
      {}
    );
  }
}
