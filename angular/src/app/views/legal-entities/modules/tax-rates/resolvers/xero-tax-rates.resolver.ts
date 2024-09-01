import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { iif, Observable, of } from 'rxjs';
import { map, switchMap } from 'rxjs/operators';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { TaxRatesService } from 'src/app/views/legal-entities/modules/tax-rates/tax-rates.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { XeroTaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/xero-tax-rate';

@Injectable({
  providedIn: 'root',
})
export class XeroTaxRatesResolver implements Resolve<XeroTaxRate[]> {
  public constructor(
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService,
    private taxRatesService: TaxRatesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<XeroTaxRate[]> | Promise<XeroTaxRate[]> | XeroTaxRate[] {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.legalEntitiesService.checkIfXeroLinked().pipe(
      map(res => {
        this.legalEntitiesService.isXeroLinked = res.is_xero_linked;
        return res;
      }),
      switchMap(res =>
        iif(
          () => res.is_xero_linked,
          this.taxRatesService.getXeroTaxRates(),
          of([])
        )
      )
    );
  }
}
