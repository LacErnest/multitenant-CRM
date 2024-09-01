import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { TablePreferencesService } from '../../../shared/services/table-preferences.service';
import { HttpParams } from '@angular/common/http';
import { ContactsService } from '../contacts.service';

@Injectable({
  providedIn: 'root',
})
export class ContactsResolver implements Resolve<any> {
  constructor(
    private contactsService: ContactsService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.contactsService.getContacts(
      this.tablePreferencesService.getTableParams(route, new HttpParams())
    );
  }
}
