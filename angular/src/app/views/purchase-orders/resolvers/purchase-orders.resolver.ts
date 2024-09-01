import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { PurchaseOrdersService } from '../purchase-orders.service';
import { TablePreferencesService } from '../../../shared/services/table-preferences.service';
import { HttpParams } from '@angular/common/http';
import { Helpers } from '../../../core/classes/helpers';

@Injectable({
  providedIn: 'root',
})
export class PurchaseOrdersResolver implements Resolve<any> {
  constructor(
    private purchaseOrdersService: PurchaseOrdersService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    const { queryParams } = route;
    let params = new HttpParams();

    if (!('return' in queryParams)) {
      for (const [key, value] of Object.entries(route.queryParams)) {
        if (Object.prototype.hasOwnProperty.call(route.queryParams, key)) {
          params = Helpers.setParam(params, key, value);
        }
      }
    }

    return this.purchaseOrdersService.getPurchaseOrders(
      this.tablePreferencesService.getTableParams(route, params)
    );
  }
}
