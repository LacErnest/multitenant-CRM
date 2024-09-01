import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { GlobalService } from '../../core/services/global.service';

export type ImportMatches = {
  property: string;
  column: string;
}[];

@Injectable({
  providedIn: 'root',
})
export class CustomersService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public get exportCustomerCallback() {
    return this.exportCustomer.bind(this);
  }

  // TODO: add type here and for methods below
  public getCustomers(params: HttpParams): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/customers`,
      { params }
    );
  }

  public getCustomer(customerID: string): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/customers/${customerID}`
    );
  }

  public createCustomer(customer: any): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/customers`,
      customer
    );
  }

  public editCustomer(customerID: string, customer: any): Observable<any> {
    return this.http.put(
      `api/${this.globalService.currentCompany?.id}/customers/${customerID}`,
      customer
    );
  }

  public importCustomer(file: string): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/customers/import`,
      { file }
    );
  }

  public finalizeImportCustomer(body: {
    id: string;
    matches: ImportMatches;
  }): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/customers/import/finalize`,
      body
    );
  }

  public deleteCustomers(customerIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      `api/${this.globalService.currentCompany?.id}/customers`,
      { body: customerIDs }
    );
  }

  // TODO: add type here and for APIs which use DownloadModalComponent + change DownloadCallback type (to use `response.body`)
  public exportCustomer(
    format: string,
    customerID: string,
    legalEntityId: string
  ): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/customers/${customerID}/export/customer/${format}`,
      { params: { legal_entity_id: legalEntityId }, responseType: 'blob' }
    );
  }

  public exportCustomers(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany.id + '/customers/export',
      { responseType: 'blob' }
    );
  }
}
