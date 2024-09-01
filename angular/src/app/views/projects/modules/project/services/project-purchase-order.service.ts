import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { GlobalService } from 'src/app/core/services/global.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ProjectPurchaseOrderService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportProjectPurchaseOrderCallback() {
    return this.exportProjectPurchaseOrder.bind(this);
  }

  getProjectPurchaseOrder(
    projectID: string,
    purchaseOrderID: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID
    );
  }

  createProjectPurchaseOrder(
    projectID: string,
    purchaseOrder: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders',
      purchaseOrder
    );
  }

  editProjectPurchaseOrder(
    projectID: string,
    purchaseOrderID: string,
    purchaseOrder: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID,
      purchaseOrder
    );
  }

  editProjectPurchaseOrderStatus(
    projectID: string,
    purchaseOrderID: string,
    status: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/status',
      status
    );
  }

  exportProjectPurchaseOrder(
    format: ExportFormat,
    projectID: string,
    purchaseOrderID: string,
    templateId: string
  ): Observable<Blob> {
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

  cloneProjectPurchaseOrder(
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

  createOrderFromProjectPurchaseOrder(
    projectID: string,
    purchaseOrderID: string
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/order',
      {}
    );
  }

  deleteProjectPurchaseOrders(
    projectID: string,
    purchaseOrderIDs: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders',
      { body: purchaseOrderIDs }
    );
  }

  addPurchaseOrderItem(
    projectID: string,
    purchaseOrderID: string,
    item: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/items',
      item
    );
  }

  editPurchaseOrderItem(
    projectID: string,
    purchaseOrderID: string,
    item: any,
    itemID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/items/' +
        itemID,
      item
    );
  }

  addPurchaseOrderModifier(
    projectID: string,
    purchaseOrderID: string,
    modifier: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/price_modifiers',
      modifier
    );
  }

  editPurchaseOrderModifier(
    projectID: string,
    purchaseOrderID: string,
    modifier: any,
    modifierID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/price_modifiers/' +
        modifierID,
      modifier
    );
  }

  deletePurchaseOrderModifier(
    projectID: string,
    purchaseOrderID: string,
    modifierID: string
  ): Observable<any> {
    return this.http.delete(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/purchase_orders/' +
        purchaseOrderID +
        '/price_modifiers/' +
        modifierID
    );
  }

  public uploadPurchaseOrderInvoice(
    resourceID: string,
    purchaseOrderID: string,
    file: string
  ): Observable<PurchaseOrder> {
    return this.http.post<PurchaseOrder>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceID}/purchase_orders/${purchaseOrderID}/invoices/upload`,
      { file }
    );
  }
}
