import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class SuggestService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  suggestResources(value: string, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/resources/suggest/' +
        value,
      { params }
    );
  }

  suggestUsers(value: string, params: any): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/users/suggest/' +
        value,
      { params }
    );
  }

  suggestSalesPersons(value: string, params: any): Observable<any> {
    return this.http.get('api/suggest/sales_persons/' + value, { params });
  }

  suggestCustomer(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/suggest/' +
        value,
      { params }
    );
  }

  suggestContact(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/contacts/suggest/' +
        value,
      { params }
    );
  }

  suggestProject(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/suggest/' +
        value,
      { params }
    );
  }

  suggestOrder(value: string | undefined, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/orders/suggest/' +
        value,
      { params }
    );
  }

  suggestService(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/services/suggest/' +
        value,
      { params }
    );
  }

  suggestEmployees(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/suggest/' +
        value,
      { params }
    );
  }

  suggestProjectManagers(
    value: string | undefined,
    params?: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/users/pm_suggest/' +
        value,
      { params }
    );
  }

  suggestLegalEntities(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/company_legal_entities/suggest/' +
        value,
      { params }
    );
  }

  suggestCompanies(
    value: string | undefined,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/suggest/' + value,
      { params }
    );
  }
}
