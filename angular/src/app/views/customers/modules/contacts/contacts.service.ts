import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class ContactsService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getContact(customerID: string, contactID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/' +
        customerID +
        '/contacts/' +
        contactID
    );
  }

  createContact(customerID: string, contact: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/' +
        customerID +
        '/contacts',
      contact
    );
  }

  editContact(
    customerID: string,
    contactID: string,
    contact: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/' +
        customerID +
        '/contacts/' +
        contactID,
      contact
    );
  }

  deleteContacts(customerID: string, contactIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/customers/' +
        customerID +
        '/contacts',
      { body: contactIDs }
    );
  }
}
