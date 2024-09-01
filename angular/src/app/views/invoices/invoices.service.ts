import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class InvoicesService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportInvoiceCallback() {
    return this.exportInvoice.bind(this);
  }

  getInvoices(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/invoices',
      { params }
    );
  }

  deleteInvoices(invoiceIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' + this.globalService.currentCompany?.id + '/invoices',
      { body: invoiceIDs }
    );
  }

  cloneInvoice(
    projectID: string,
    invoiceID: string,
    destination_id: string
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

  exportInvoice(
    format: string,
    projectID: string,
    invoiceID: string,
    templateId: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/export/' +
        templateId +
        '/' +
        format,
      { responseType: 'blob' }
    );
  }

  exportInvoices(params?: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/invoices/export',
      { responseType: 'blob', params: params }
    );
  }

  getProjectInvoices(projectID: string, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices',
      { params }
    );
  }

  getSingleFromProject(projectID: string, invoiceID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID
    );
  }
}
