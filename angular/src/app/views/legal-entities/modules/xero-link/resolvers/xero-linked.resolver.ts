import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class XeroLinkedResolver implements Resolve<boolean> {
  public constructor(
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;

    if (this.legalEntitiesService.legalEntityId !== routeId) {
      this.legalEntitiesService.legalEntityId = routeId;
    }

    return this.legalEntitiesService.checkIfXeroLinked().pipe(
      map(r => {
        this.legalEntitiesService.isXeroLinked = r.is_xero_linked;
        return r.is_xero_linked;
      })
    );
  }
}
