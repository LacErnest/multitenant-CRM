import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ExportFormat } from '../../shared/enums/export.format';

@Injectable({
  providedIn: 'root',
})
export class ExternalAccessService {
  public userToken: string;

  public constructor(private http: HttpClient) {}

  public getResource(companyID: string, resourceID: string): Observable<any> {
    return this.http.get('api/' + companyID + '/resources/' + resourceID, {
      headers: this.getAuthHeader(),
    });
  }

  public editResource(
    companyID: string,
    resourceID: string,
    resource: any
  ): Observable<any> {
    return this.http.put(
      'api/' + companyID + '/resources/' + resourceID,
      resource,
      { headers: this.getAuthHeader() }
    );
  }

  public uploadInvoice(
    companyID: string,
    resourceID: string,
    purchaseOrderID: string,
    file: string
  ): Observable<any> {
    return this.http.post(
      `api/${companyID}/resources/${resourceID}/purchase_orders/${purchaseOrderID}/invoices/upload`,
      { file },
      { headers: this.getAuthHeader() }
    );
  }

  public get downloadInvoiceCallback(): any {
    return this.downloadInvoice.bind(this);
  }

  public downloadInvoice(
    format: string,
    companyID: string,
    resourceID: string,
    purchaseOrderID: string,
    invoiceID: string
  ): Observable<Blob> {
    return this.http.get(
      `api/${companyID}/resources/${resourceID}/purchase_orders/${purchaseOrderID}/invoices/${invoiceID}/download`,
      { responseType: 'blob', headers: this.getAuthHeader() }
    );
  }

  public getTablePreference(
    companyID: string,
    entity: string,
    token: string
  ): Observable<any> {
    return this.http.get(
      'api/' + companyID + '/table_preferences/users/' + entity.toString(),
      { headers: this.getAuthHeader() }
    );
  }

  public get downloadPurchaseOrderCallback() {
    return this.downloadPurchaseOrder.bind(this);
  }

  public downloadPurchaseOrder(
    format: ExportFormat,
    companyID: string,
    resourceID: string,
    purchaseOrderID: string
  ): Observable<Blob> {
    return this.http.get(
      `api/${companyID}/resources/${resourceID}/purchase_orders/${purchaseOrderID}/export/${format}`,
      { responseType: 'blob', headers: this.getAuthHeader() }
    );
  }

  private getAuthHeader(): HttpHeaders {
    return new HttpHeaders().set('X-Auth', this.userToken);
  }
}
