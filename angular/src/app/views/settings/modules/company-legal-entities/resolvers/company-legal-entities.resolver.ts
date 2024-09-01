import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { CompanyLegalEntitiesList } from 'src/app/shared/interfaces/legal-entity';
import { CompanyLegalEntitiesService } from 'src/app/views/settings/modules/company-legal-entities/company-legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class CompanyLegalEntitiesResolver
  implements Resolve<CompanyLegalEntitiesList>
{
  public constructor(
    private companyLegalEntitiesService: CompanyLegalEntitiesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ):
    | Observable<CompanyLegalEntitiesList>
    | Promise<CompanyLegalEntitiesList>
    | CompanyLegalEntitiesList {
    return this.companyLegalEntitiesService.getCompanyLegalEntities();
  }
}
