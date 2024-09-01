import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { GlobalService } from 'src/app/core/services/global.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { InvoiceStatusUpdate } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update';
import { InvoiceStatusUpdateResponse } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update-response';
import { JsonValue } from 'src/app/shared/types/json-value';
import { EmailTemplate } from 'src/app/views/settings/modules/email-management/interfaces/email-template';
@Injectable({
  providedIn: 'root',
})
export class ProjectInvoiceService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public get exportProjectInvoiceCallback() {
    return this.exportProjectInvoice.bind(this);
  }

  public getProjectInvoice(
    projectID: string,
    invoiceID: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID
    );
  }

  public createProjectInvoice(
    projectID: string,
    invoice: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices',
      invoice
    );
  }

  public editProjectInvoice(
    projectID: string,
    invoiceID: string,
    invoice: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID,
      invoice
    );
  }

  public changeProjectInvoiceStatus(
    projectID: string,
    invoiceID: string,
    invoiceStatus: InvoiceStatusUpdate,
    params?: JsonValue
  ): Observable<InvoiceStatusUpdateResponse> {
    if (!params) {
      params = {};
    }
    for (const key in invoiceStatus) {
      params[key] = invoiceStatus[key];
    }
    return this.http.put<InvoiceStatusUpdateResponse>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/invoices/${invoiceID}/status`,
      params
    );
  }

  public toggleSendingClientRemindersStatus(
    projectID: string,
    invoiceID: string
  ): Observable<boolean> {
    return this.http.put<boolean>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/invoices/${invoiceID}/sending-reminders-status`,
      {}
    );
  }

  public exportProjectInvoice(
    format: ExportFormat,
    projectID: string,
    invoiceID: string,
    templateId: string
  ): Observable<Blob> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/invoices/${invoiceID}/export/${templateId}/${format}`,
      { responseType: 'blob' }
    );
  }

  public cloneProjectInvoice(
    projectID: string,
    invoiceID: string,
    destination_id: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/clone',
      { destination_id }
    );
  }

  public deleteProjectInvoices(
    projectID: string,
    invoiceIDs: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices',
      { body: invoiceIDs }
    );
  }

  public addInvoiceItem(
    projectID: string,
    invoiceID: string,
    item: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/items',
      item
    );
  }

  public editInvoiceItem(
    projectID: string,
    invoiceID: string,
    item: any,
    itemID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/items/' +
        itemID,
      item
    );
  }

  public addInvoiceModifier(
    projectID: string,
    invoiceID: string,
    modifier: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/price_modifiers',
      modifier
    );
  }

  public editInvoiceModifier(
    projectID: string,
    invoiceID: string,
    modifier: any,
    modifierID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/price_modifiers/' +
        modifierID,
      modifier
    );
  }

  public deleteInvoiceModifier(
    projectID: string,
    invoiceID: string,
    modifierID: string
  ): Observable<void> {
    return this.http.delete<void>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/invoices/${invoiceID}/price_modifiers/${modifierID}`
    );
  }

  public getProjectEmailTemplate(
    projectID: string,
    invoiceID: string
  ): Observable<EmailTemplate> {
    return this.http.get<EmailTemplate>(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/email-template'
    );
  }
}
