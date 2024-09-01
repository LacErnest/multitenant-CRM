import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { ExportFormat } from '../../shared/enums/export.format';

@Injectable({
  providedIn: 'root',
})
export class OrdersService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportOrderCallback() {
    return this.exportOrder.bind(this);
  }

  getOrders(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/orders',
      { params }
    );
  }

  deleteOrders(orderIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' + this.globalService.currentCompany?.id + '/orders',
      { body: orderIDs }
    );
  }

  cloneOrder(projectID: string, orderID: string): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/clone',
      {}
    );
  }

  exportOrder(
    format: ExportFormat,
    projectID: string,
    orderID: string,
    templateId: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/export/' +
        templateId +
        '/' +
        format,
      { responseType: 'blob' }
    );
  }

  exportOrders(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/orders/export',
      { responseType: 'blob' }
    );
  }

  parsedOrders(): Observable<any> {
    return this.http.get('api/orders-parsed');
  }
}
