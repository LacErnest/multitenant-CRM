import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { UsersService } from '../users.service';
import { HttpParams } from '@angular/common/http';
import { TablePreferencesService } from '../../../../../shared/services/table-preferences.service';

@Injectable({
  providedIn: 'root',
})
export class UsersResolver implements Resolve<any> {
  constructor(
    private usersService: UsersService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.usersService.getUsers(
      route.queryParams.return
        ? this.tablePreferencesService.getTableHttpParams(
            route.data.entity,
            route.data.preferences
          )
        : new HttpParams()
    );
  }
}
