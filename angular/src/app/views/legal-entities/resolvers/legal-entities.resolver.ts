import { HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { Helpers } from 'src/app/core/classes/helpers';
import { LegalEntitiesList } from 'src/app/shared/interfaces/legal-entity';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class LegalEntitiesResolver implements Resolve<LegalEntitiesList> {
  public constructor(
    private legalEntitiesService: LegalEntitiesService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ):
    | Observable<LegalEntitiesList>
    | Promise<LegalEntitiesList>
    | LegalEntitiesList {
    const { queryParams } = route;
    let params = new HttpParams();

    if (!('return' in queryParams)) {
      for (const [key, value] of Object.entries(route.queryParams)) {
        if (Object.prototype.hasOwnProperty.call(route.queryParams, key)) {
          params = Helpers.setParam(params, key, value);
        }
      }
    }

    return this.legalEntitiesService.getLegalEntities(
      this.tablePreferencesService.getTableParams(route, params)
    );
  }
}
