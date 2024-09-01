import { HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { Helpers } from 'src/app/core/classes/helpers';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ResourceInvoiceList } from 'src/app/views/resource-invoices/interfaces/resource-invoice-list';
import { ResourceInvoicesService } from 'src/app/views/resource-invoices/resource-invoices.service';

@Injectable({
  providedIn: 'root',
})
export class ResourceInvoicesResolver implements Resolve<ResourceInvoiceList> {
  constructor(
    private resourceInvoicesService: ResourceInvoicesService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<ResourceInvoiceList>
    | Promise<ResourceInvoiceList>
    | ResourceInvoiceList {
    const { queryParams } = route;
    let params = new HttpParams();

    if (!('return' in queryParams)) {
      for (const [key, value] of Object.entries(route.queryParams)) {
        if (Object.prototype.hasOwnProperty.call(route.queryParams, key)) {
          params = Helpers.setParam(params, key, value);
        }
      }
    }

    return this.resourceInvoicesService.getResourceInvoices(
      this.tablePreferencesService.getTableParams(route, params)
    );
  }
}
