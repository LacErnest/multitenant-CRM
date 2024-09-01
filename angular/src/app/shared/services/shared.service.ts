import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { CurrentTaxRate } from 'src/app/shared/interfaces/current-tax-rate';
import { Customer } from 'src/app/shared/interfaces/customer';
import { PurchaseOrder } from '../interfaces/entities';

@Injectable({
  providedIn: 'root',
})
export class SharedService {
  // TODO: refactor service
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportProjectQuoteCallback() {
    return this.exportProjectQuote.bind(this);
  }

  getCustomer(customerID: string, params: HttpParams): Observable<Customer> {
    return this.http.get<Customer>(
      `api/${this.globalService.currentCompany?.id}/customers/${customerID}`,
      { params }
    );
  }

  // TODO: use template strings
  linkXero(code): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany.id + '/xero/auth',
      { code }
    );
  }

  //#region quotes
  getProjectQuote(projectID: string, quoteID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID
    );
  }

  createQuote(quote: any): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/quotes',
      quote
    );
  }

  createProjectQuote(projectID: string, quote: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes',
      quote
    );
  }

  editProjectQuote(
    projectID: string,
    quoteID: string,
    quote: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID,
      quote
    );
  }

  changeProjectQuoteStatus(
    projectID: string,
    quoteID: string,
    status: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/status',
      status
    );
  }

  public exportProjectQuote(
    format: ExportFormat,
    projectID: string,
    quoteID: string,
    templateId: string
  ): Observable<Blob> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/quotes/${quoteID}/export/${templateId}/${format}`,
      { responseType: 'blob' }
    );
  }

  cloneProjectQuote(projectID: string, quoteID: string): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/clone',
      {}
    );
  }

  createOrderFromProjectQuote(
    projectID: string,
    quoteID: string
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/order',
      {}
    );
  }

  deleteProjectQuotes(projectID: string, quoteIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes',
      { body: quoteIDs }
    );
  }

  //#endregion

  //#region quote items
  // TODO: make this and similar items methods generic in the SharedProjectEntityService
  addQuoteItem(projectID: string, quoteID: string, item: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/items',
      item
    );
  }

  editQuoteItem(
    projectID: string,
    quoteID: string,
    item: any,
    itemID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/items/' +
        itemID,
      item
    );
  }

  //#endregion

  //#region quote items
  addQuoteModifier(
    projectID: string,
    quoteID: string,
    modifier: EntityPriceModifier
  ): Observable<EntityPriceModifier> {
    return this.http.post<EntityPriceModifier>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/quotes/${quoteID}/price_modifiers`,
      modifier
    );
  }

  editQuoteModifier(
    projectID: string,
    quoteID: string,
    modifier: any,
    modifierID: string
  ): Observable<EntityPriceModifier> {
    return this.http.put<EntityPriceModifier>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/quotes/${quoteID}/price_modifiers/${modifierID}`,
      modifier
    );
  }

  // TODO: unify/make generic similar quote/order/invoice/PO APIs
  deleteQuoteModifier(
    projectID: string,
    quoteID: string,
    modifierID: string
  ): Observable<void> {
    return this.http.delete<void>(
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/quotes/${quoteID}/price_modifiers/${modifierID}`
    );
  }

  //#endregion
  getPriceModifiers(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/price_modifiers'
    );
  }

  public getCurrentTaxRate(): Observable<CurrentTaxRate> {
    return this.http.get<CurrentTaxRate>(
      `api/${this.globalService.currentCompany?.id}/current_rate`
    );
  }

  public uploadDocument(
    entityID: string,
    projectId: string,
    file: string,
    entity: string
  ): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/projects/${projectId}/${entity}/${entityID}/document`,
      { file }
    );
  }

  public deleteDocument(
    entityID: string,
    projectId: string,
    entity: string
  ): Observable<any> {
    return this.http.delete(
      `api/${this.globalService.currentCompany?.id}/projects/${projectId}/${entity}/${entityID}/document`
    );
  }

  public createPurchaseOrder(purchaseOrder: any): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/purchase_orders',
      purchaseOrder
    );
  }

  public getTemplates(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/templatecategories'
    );
  }
}
