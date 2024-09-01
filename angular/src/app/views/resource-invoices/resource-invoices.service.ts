import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { ResourceInvoiceList } from 'src/app/views/resource-invoices/interfaces/resource-invoice-list';

@Injectable({
  providedIn: 'root',
})
export class ResourceInvoicesService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public getResourceInvoices(
    params: HttpParams
  ): Observable<ResourceInvoiceList> {
    return this.http.get<ResourceInvoiceList>(
      `api/${this.globalService.currentCompany?.id}/resource_invoices`,
      { params }
    );
  }

  exportResourceInvoices(params?: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/resource_invoices/export',
      { responseType: 'blob', params: params }
    );
  }

  getResourceInvoicesProject(
    projectID: string,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/resource_invoices/' +
        projectID,
      { params }
    );
  }
}
