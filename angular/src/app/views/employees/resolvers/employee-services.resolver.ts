import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ServicesService } from 'src/app/shared/services/services.service';

@Injectable({
  providedIn: 'root',
})
export class EmployeeServicesResolver implements Resolve<any> {
  constructor(private servicesService: ServicesService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.servicesService.getResourceServices(route.params.employee_id);
  }
}
