import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { GlobalService } from '../../../../../core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class ProjectOrderService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportProjectOrderCallback() {
    return this.exportProjectOrder.bind(this);
  }

  getProjectOrder(projectID: string, orderID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID
    );
  }

  createProjectOrder(projectID: string, order: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders',
      order
    );
  }

  editProjectOrder(
    projectID: string,
    orderID: string,
    order: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID,
      order
    );
  }

  changeProjectOrderStatus(
    projectID: string,
    orderID: string,
    status: number,
    needInvoice: boolean,
    orderDate?: string
  ): Observable<any> {
    return this.http.put(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/orders/${orderID}/status`,
      {
        status,
        date: orderDate,
        need_invoice: needInvoice,
      }
    );
  }

  exportProjectOrder(
    format: string,
    projectID: string,
    orderID: string,
    templateId: string
  ): Observable<Blob> {
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

  exportProjectOrderReport(
    projectID: string,
    orderID: string
  ): Observable<Blob> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/export/report',
      { responseType: 'blob' }
    );
  }

  cloneProjectOrder(projectID: string, orderID: string): Observable<any> {
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

  deleteProjectOrders(projectID: string, orderIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders',
      { body: orderIDs }
    );
  }

  addOrderItem(projectID: string, orderID: string, item: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/items',
      item
    );
  }

  editOrderItem(
    projectID: string,
    orderID: string,
    item: any,
    itemID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/items/' +
        itemID,
      item
    );
  }

  addOrderModifier(
    projectID: string,
    orderID: string,
    modifier: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/price_modifiers',
      modifier
    );
  }

  editOrderModifier(
    projectID: string,
    orderID: string,
    modifier: any,
    modifierID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/price_modifiers/' +
        modifierID,
      modifier
    );
  }

  deleteOrderModifier(
    projectID: string,
    orderID: string,
    modifierID: string
  ): Observable<any> {
    return this.http.delete(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/orders/' +
        orderID +
        '/price_modifiers/' +
        modifierID
    );
  }
}
