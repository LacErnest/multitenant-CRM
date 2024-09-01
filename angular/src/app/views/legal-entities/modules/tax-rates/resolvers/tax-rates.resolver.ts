import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { TaxRatesService } from 'src/app/views/legal-entities/modules/tax-rates/tax-rates.service';
import { TaxRateList } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate-list';

@Injectable({
  providedIn: 'root',
})
export class TaxRatesResolver implements Resolve<TaxRateList> {
  public constructor(
    private legalEntitiesService: LegalEntitiesService,
    private taxRatesService: TaxRatesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<TaxRateList> | Promise<TaxRateList> | TaxRateList {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.taxRatesService.getTaxRates(
      this.legalEntitiesService.legalEntityId
    );
  }
}
