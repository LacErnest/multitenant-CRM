import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';

@Injectable({
  providedIn: 'root',
})
export class DatatablePreferencesResolver implements Resolve<any> {
  constructor(private tablePreferencesService: TablePreferencesService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.tablePreferencesService.getTablePreferences(
      route.data.preferences ?? 'users',
      route.data.entity
    );
  }
}
