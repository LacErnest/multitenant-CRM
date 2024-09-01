import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ContactsService } from '../contacts.service';

@Injectable({
  providedIn: 'root',
})
export class ContactResolver implements Resolve<any> {
  constructor(private contactsService: ContactsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    const { contact_id, customer_id } = route.params;
    return this.contactsService.getContact(customer_id, contact_id);
  }
}
