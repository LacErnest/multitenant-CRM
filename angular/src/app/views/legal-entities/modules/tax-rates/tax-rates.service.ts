import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { GlobalService } from 'src/app/core/services/global.service';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { TaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate';
import { TaxRateList } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate-list';
import { XeroTaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/xero-tax-rate';

@Injectable({
  providedIn: 'root',
})
export class TaxRatesService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public get legalEntityId(): string {
    return this.legalEntitiesService.legalEntityId;
  }

  public getTaxRates(legalEntityId: string): Observable<TaxRateList> {
    return this.http.get<TaxRateList>(
      `api/legal_entities/${legalEntityId}/rates`
    );
  }

  public getTaxRate(taxRateId: string): Observable<TaxRate> {
    return this.http.get<TaxRate>(
      `api/legal_entities/${this.legalEntityId}/rates/${taxRateId}`
    );
  }

  public createTaxRate(taxRate: TaxRate): Observable<TaxRate> {
    return this.http.post<TaxRate>(
      `api/legal_entities/${this.legalEntityId}/rates`,
      taxRate
    );
  }

  public editTaxRate(taxRate: TaxRate): Observable<TaxRate> {
    return this.http.patch<TaxRate>(
      `api/legal_entities/${this.legalEntityId}/rates/${taxRate.id}`,
      taxRate
    );
  }

  public deleteTaxRate(taxRateId: string): Observable<string> {
    return this.http.delete<string>(
      `api/legal_entities/${this.legalEntityId}/rates/${taxRateId}`
    );
  }

  public getXeroTaxRates(): Observable<XeroTaxRate[]> {
    return this.http.get<XeroTaxRate[]>(
      `api/legal_entities/${this.legalEntityId}/xero/tax_rates`
    );
  }
}
