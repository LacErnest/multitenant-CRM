import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { TablePreferencesService } from '../../../shared/services/table-preferences.service';
import { HttpParams } from '@angular/common/http';
import { EmployeesService } from '../employees.service';

@Injectable({
  providedIn: 'root',
})
export class EmployeesResolver implements Resolve<any> {
  constructor(
    private employeesService: EmployeesService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.employeesService.getEmployees(
      this.tablePreferencesService.getTableParams(route, new HttpParams())
    );
  }
}
