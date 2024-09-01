import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class InvoicePaymentsService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportInvoicePaymentCallback() {
    return this.exportInvoicePayments.bind(this);
  }

  getInvoicePayments(invoiceID: string, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/invoices/' +
        invoiceID +
        '/payments',
      { params }
    );
  }

  deleteInvoicePayments(
    invoiceID: string,
    invoicePaymentIDs: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/invoices/' +
        invoiceID +
        '/payments',
      { body: invoicePaymentIDs }
    );
  }

  cloneInvoicePayment(
    projectID: string,
    invoiceID: string,
    invoicePaymentId: string,
    destination_id: string
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments/' +
        invoicePaymentId +
        '/clone',
      { destination_id }
    );
  }

  exportInvoicePayment(
    format: string,
    projectID: string,
    invoiceID: string,
    invoicePaymentID: string,
    templateId: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments/' +
        invoicePaymentID +
        '/export/' +
        templateId +
        '/' +
        format,
      { responseType: 'blob' }
    );
  }

  exportInvoicePayments(invoiceId: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/invoices/' +
        invoiceId +
        '/export',
      { responseType: 'blob' }
    );
  }

  getProjectInvoices(
    projectID: string,
    invoiceId: string,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceId,
      { params }
    );
  }
}
