import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { EnumService } from '../../core/services/enum.service';
import { Observable, of } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class InvoiceTypeEnumResolver implements Resolve<any> {
  constructor(private enumService: EnumService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    if (!this.enumService.hasEnum('invoicetype')) {
      return this.enumService
        .getEnums(['invoice.type'])
        .toPromise()
        .then(result => {
          this.enumService.setEnums(result);
        });
    } else {
      return of(true);
    }
  }
}
