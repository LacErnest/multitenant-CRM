import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';
import { ExportFormat } from '../../shared/enums/export.format';

@Injectable({
  providedIn: 'root',
})
export class PurchaseOrdersService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportPurchaseOrderCallback() {
    return this.exportPurchaseOrder.bind(this);
  }

  getPurchaseOrders(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/purchase_orders',
      { params }
    );
  }

  deletePurchaseOrders(purchaseOrderIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' + this.globalService.currentCompany?.id + '/purchase_orders',
      { body: purchaseOrderIDs }
    );
  }

  clonePurchaseOrder(
    projectID: string,
    purchaseOrderID: string,
    destination_id: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/clone',
      { destination_id }
    );
  }

  exportPurchaseOrder(
    format: ExportFormat,
    projectID: string,
    purchaseOrderID: string,
    templateId: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/export/' +
        templateId +
        '/' +
        format,
      { responseType: 'blob' }
    );
  }

  exportPurchaseOrders(params?: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/purchase_orders/export',
      { responseType: 'blob', params: params }
    );
  }

  getProjectPurchaseOrders(
    projectID: string,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders',
      { params }
    );
  }
}
