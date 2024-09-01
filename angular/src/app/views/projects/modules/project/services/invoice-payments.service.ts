import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../../../../core/services/global.service';
import { Observable } from 'rxjs';
import { InvoicePayment } from 'src/app/shared/interfaces/entities';
import { InvoiceStatusUpdate } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update';
@Injectable({
  providedIn: 'root',
})
export class InvoicePaymentsService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportInvoicePaymentCallback() {
    return this.exportInvoicePayment.bind(this);
  }

  getInvoicePayments(projectID: string, invoiceID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments',
      {}
    );
  }

  getInvoicesPayments(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/invoices/payments',
      { params }
    );
  }

  createInvoicePayment(
    projectID: string,
    invoiceID: string,
    invoicePayment: InvoicePayment
  ): Observable<any> {
    return this.http.request(
      'post',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments',
      { body: invoicePayment }
    );
  }

  editInvoicePayment(
    projectID: string,
    invoiceID: string,
    invoicePaymentID: string,
    invoicePayment: InvoicePayment
  ): Observable<any> {
    return this.http.request(
      'put',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments/' +
        invoicePaymentID,
      { body: invoicePayment }
    );
  }

  getInvoicePayment(
    projectID: string,
    invoiceID: string,
    invoicePaymentID: string
  ): Observable<any> {
    return this.http.request(
      'get',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments/' +
        invoicePaymentID
    );
  }

  deleteInvoicePayments(
    projectID: string,
    invoiceID: string,
    invoicePaymentIDs: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
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

  exportInvoicePayments(projectID: string, invoiceId: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceId +
        '/export',
      { responseType: 'blob' }
    );
  }

  getProjectInvoicePayments(
    projectID: string,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/payments',
      { params }
    );
  }

  getTotalPaidAmountForInvoice(
    projectID: string,
    invoiceID: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/invoices/' +
        invoiceID +
        '/payments/analyse/paid-amount'
    );
  }

  public changeInvoicePaymentStatus(
    projectID: string,
    invoiceID: string,
    invoicePaymentID: string,
    invoiceStatus: InvoiceStatusUpdate
  ): Observable<InvoicePayment> {
    return this.http.put<InvoicePayment>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/invoices/${invoiceID}/payments/${invoicePaymentID}/status`,
      invoiceStatus
    );
  }
}
