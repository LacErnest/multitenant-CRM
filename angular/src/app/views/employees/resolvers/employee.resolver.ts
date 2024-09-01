import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { EmployeesService } from '../employees.service';

@Injectable({
  providedIn: 'root',
})
export class EmployeeResolver implements Resolve<any> {
  constructor(private employeesService: EmployeesService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.employeesService.getEmployee(route.params.employee_id);
  }
}
