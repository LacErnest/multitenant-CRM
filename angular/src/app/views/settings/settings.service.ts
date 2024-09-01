import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { CompanyTemplates } from 'src/app/shared/interfaces/template';
import { TemplateType } from 'src/app/shared/types/template-type';
import { Loan, LoanList } from 'src/app/views/settings/interfaces/loan';
import { TemplateModel } from '../../shared/interfaces/template-model';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';
@Injectable({
  providedIn: 'root',
})
export class SettingsService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public getTemplatesTypes(): Observable<TemplateModel[]> {
    return this.http.get<TemplateModel[]>(
      `api/${this.globalService.currentCompany?.id}/templatecategories`
    );
  }

  public getCompanyTemplates(templateID: string): Observable<CompanyTemplates> {
    return this.http.get<CompanyTemplates>(
      `api/${this.globalService.currentCompany?.id}/templates/${templateID}`
    );
  }

  public getCompanyTemplate(templateID: string): Observable<TemplateModel> {
    return this.http.get<TemplateModel>(
      `api/${this.globalService.currentCompany?.id}/templates/${templateID}/view`
    );
  }

  // TODO: add types
  public uploadTemplate(
    entity: string,
    file: any,
    templateId: string
  ): Observable<any> {
    return this.http.put(
      `api/${this.globalService.currentCompany?.id}/templates/${templateId}/${entity}`,
      { file }
    );
  }

  public getTemplate(
    entity: TemplateType,
    format: ExportFormat,
    templateId: string
  ): Observable<HttpResponse<Blob>> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/templates/${templateId}/${entity}/${format}`,
      { responseType: 'blob', observe: 'response' }
    );
  }

  public getLoans(params?: HttpParams): Observable<LoanList> {
    return this.http.get<LoanList>(
      `api/${this.globalService.currentCompany?.id}/loans`,
      { params }
    );
  }

  public getLoan(id: string): Observable<Loan> {
    return this.http.get<Loan>(
      `api/${this.globalService.currentCompany?.id}/loans/${id}`
    );
  }

  public addLoan(loan: Loan): Observable<Loan> {
    return this.http.post<Loan>(
      `api/${this.globalService.currentCompany?.id}/loans`,
      loan
    );
  }

  public editLoan(loanID: string, loan: Loan): Observable<Loan> {
    return this.http.patch<Loan>(
      `api/${this.globalService.currentCompany?.id}/loans/${loanID}`,
      loan
    );
  }

  public deleteLoan(loanID: string): Observable<void> {
    return this.http.delete<void>(
      `api/${this.globalService.currentCompany?.id}/loans/${loanID}`
    );
  }

  public addTemplate(template: TemplateModel): Observable<TemplateModel> {
    return this.http.post<TemplateModel>(
      `api/${this.globalService.currentCompany?.id}/templatecategories`,
      template
    );
  }

  public editTemplate(
    templateID: string,
    template: TemplateModel
  ): Observable<TemplateModel> {
    return this.http.put<TemplateModel>(
      `api/${this.globalService.currentCompany?.id}/templatecategories/${templateID}`,
      template
    );
  }

  public deleteTemplate(templateID: string): Observable<void> {
    return this.http.delete<void>(
      `api/${this.globalService.currentCompany?.id}/templatecategories/${templateID}`
    );
  }

  public getSettings(companyId: string): Observable<CompanySetting> {
    return this.http.get<CompanySetting>(`api/${companyId}/settings`);
  }

  public editCompanySetting(
    companyId: string,
    setting: any
  ): Observable<CompanySetting> {
    return this.http.patch<CompanySetting>(
      `api/${companyId}/settings`,
      setting
    );
  }

  public createCompanySetting(
    companyId: string,
    setting: any
  ): Observable<CompanySetting> {
    return this.http.post<CompanySetting>(`api/${companyId}/settings`, setting);
  }
}
