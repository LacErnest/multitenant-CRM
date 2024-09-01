import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { CustomersService } from '../customers.service';
import { TablePreferencesService } from '../../../shared/services/table-preferences.service';
import { HttpParams } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class CustomersResolver implements Resolve<any> {
  constructor(
    private customersService: CustomersService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.customersService.getCustomers(
      this.tablePreferencesService.getTableParams(route, new HttpParams())
    );
  }
}
