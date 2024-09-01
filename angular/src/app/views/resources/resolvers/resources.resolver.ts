import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ResourcesService } from '../resources.service';
import { TablePreferencesService } from '../../../shared/services/table-preferences.service';
import { HttpParams } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class ResourcesResolver implements Resolve<any> {
  constructor(
    private resourcesService: ResourcesService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.resourcesService.getResources(
      route.queryParams.return
        ? this.tablePreferencesService.getTableHttpParams(
            route.data.entity,
            route.data.preferences
          )
        : new HttpParams()
    );
  }
}
