import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { ContractTemplates } from 'src/app/shared/interfaces/template';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { ContractTemplatesService } from 'src/app/views/legal-entities/modules/contract-templates/contract-templates.service';

@Injectable({
  providedIn: 'root',
})
export class ContractTemplatesResolver implements Resolve<ContractTemplates> {
  public constructor(
    private contractTemplatesService: ContractTemplatesService,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ):
    | Observable<ContractTemplates>
    | Promise<ContractTemplates>
    | ContractTemplates {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.contractTemplatesService.getContractTemplates(
      this.legalEntitiesService.legalEntityId
    );
  }
}
