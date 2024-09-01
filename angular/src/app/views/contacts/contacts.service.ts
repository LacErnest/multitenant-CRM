import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { GlobalService } from '../../core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class ContactsService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getContacts(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany.id + '/contacts',
      { params }
    );
  }

  exportContacts(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany.id + '/contacts/export',
      { responseType: 'blob' }
    );
  }
}
