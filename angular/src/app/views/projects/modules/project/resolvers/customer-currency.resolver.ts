import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable, of } from 'rxjs';
import { ProjectService } from '../project.service';

@Injectable({
  providedIn: 'root',
})
export class CustomerCurrencyResolver implements Resolve<any> {
  constructor(private projectService: ProjectService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return route.parent.data.project
      ? this.projectService.getCustomerCurrency(
          route.parent.data.project.customer.id
        )
      : of(null);
  }
}
