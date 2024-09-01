import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ExternalAccessService } from '../external-access.service';

@Injectable({
  providedIn: 'root',
})
export class ExternalAccessResourceResolver implements Resolve<any> {
  constructor(private externalAccessService: ExternalAccessService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.externalAccessService.getResource(
      route.params.company_id,
      route.params.resource_id
    );
  }
}
