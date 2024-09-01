import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ExportFormat } from '../../../../../shared/enums/export.format';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../../../../core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class ProjectQuoteService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

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

  exportProjectQuote(
    projectID: string,
    quoteID: string,
    format: ExportFormat
  ): Observable<Blob> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/export/' +
        format,
      { responseType: 'blob' }
    );
  }

  cloneProjectQuote(
    projectID: string,
    quoteID: string,
    destination_id: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/clone',
      { destination_id }
    );
  }

  createOrderFromProjectQuote(
    projectID: string,
    quoteID: string,
    deadline: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/order',
      deadline
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

  deleteQuoteItem(
    projectID: string,
    quoteID: string,
    itemID: string
  ): Observable<any> {
    return this.http.delete(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/items/' +
        itemID
    );
  }

  //#endregion

  //#region quote items
  addQuoteModifier(
    projectID: string,
    quoteID: string,
    modifier: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/price_modifiers',
      modifier
    );
  }

  editQuoteModifier(
    projectID: string,
    quoteID: string,
    modifier: any,
    modifierID: string
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/price_modifiers/' +
        modifierID,
      modifier
    );
  }

  deleteQuoteModifier(
    projectID: string,
    quoteID: string,
    modifierID: string
  ): Observable<any> {
    return this.http.delete(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/price_modifiers/' +
        modifierID
    );
  }

  //#endregion

  getCustomer(customerID: string, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/' +
        customerID,
      { params }
    );
  }
}
