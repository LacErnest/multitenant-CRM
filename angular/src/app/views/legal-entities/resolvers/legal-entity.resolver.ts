import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { LegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class LegalEntityResolver implements Resolve<LegalEntity> {
  public constructor(private legalEntitiesService: LegalEntitiesService) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<LegalEntity> | Promise<LegalEntity> | LegalEntity {
    this.legalEntitiesService.legalEntityId = route.params.legal_entity_id;

    return this.legalEntitiesService.getLegalEntity(
      route.params.legal_entity_id
    );
  }
}
