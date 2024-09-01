import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class XeroRedirectService {
  public constructor(private http: HttpClient) {}

  // TODO: add type
  public linkXero(legalEntityId: string, code: string): Observable<any> {
    return this.http.post(`api/legal_entities/${legalEntityId}/xero/auth`, {
      code,
    });
  }
}
