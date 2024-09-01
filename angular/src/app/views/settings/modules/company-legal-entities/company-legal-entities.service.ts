import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  CompanyLegalEntitiesList,
  CompanyLegalEntity,
  LegalEntitiesList,
  LegalEntity,
} from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesSuggestions } from 'src/app/views/settings/modules/company-legal-entities/interfaces/legal-entities-suggestions';

@Injectable({
  providedIn: 'root',
})
export class CompanyLegalEntitiesService {
  public constructor(
    private globalService: GlobalService,
    private http: HttpClient
  ) {}

  private get companyId(): string {
    return this.globalService.currentCompany?.id;
  }

  public getCompanyLegalEntities(
    params?: HttpParams
  ): Observable<CompanyLegalEntitiesList> {
    return this.http.get<CompanyLegalEntitiesList>(
      `api/${this.companyId}/company_legal_entities`,
      { params }
    );
  }

  public addLegalEntityToCompany(
    legalEntityId: string
  ): Observable<CompanyLegalEntity> {
    return this.http.post<CompanyLegalEntity>(
      `api/${this.companyId}/company_legal_entities/${legalEntityId}`,
      {}
    );
  }

  public removeLegalEntityFromCompany(legalEntityId: string): Observable<void> {
    return this.http.delete<void>(
      `api/${this.companyId}/company_legal_entities/${legalEntityId}`
    );
  }

  public markLegalEntityAsDefault(legalEntityId: string): Observable<void> {
    return this.http.patch<void>(
      `api/${this.companyId}/company_legal_entities/${legalEntityId}/default`,
      {}
    );
  }

  public suggestLegalEntity(
    value: string
  ): Observable<LegalEntitiesSuggestions> {
    return this.http.get<LegalEntitiesSuggestions>(
      `api/${this.companyId}/company_legal_entities/suggest/${value}`
    );
  }

  public markLegalEntityAsLocal(legalEntityId: string): Observable<void> {
    return this.http.patch<void>(
      `api/${this.companyId}/company_legal_entities/${legalEntityId}/local`,
      {}
    );
  }
}
