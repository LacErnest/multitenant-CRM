import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { TaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate';
import { TaxRatesService } from 'src/app/views/legal-entities/modules/tax-rates/tax-rates.service';

@Injectable({
  providedIn: 'root',
})
export class TaxRateResolver implements Resolve<TaxRate> {
  public constructor(
    private legalEntitiesService: LegalEntitiesService,
    private taxRatesService: TaxRatesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<TaxRate> | Promise<TaxRate> | TaxRate {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.taxRatesService.getTaxRate(route.params.id);
  }
}
