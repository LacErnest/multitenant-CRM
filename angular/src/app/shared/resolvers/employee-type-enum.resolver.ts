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
export class EmployeeTypeEnumResolver implements Resolve<any> {
  constructor(private enumService: EnumService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    if (!this.enumService.hasEnum('employeetype')) {
      return this.enumService
        .getEnums(['employee.type'])
        .toPromise()
        .then(result => {
          this.enumService.setEnums(result);
        });
    } else {
      return of(true);
    }
  }
}
