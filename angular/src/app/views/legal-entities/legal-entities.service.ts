import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { Helpers } from 'src/app/core/classes/helpers';
import { GlobalService } from 'src/app/core/services/global.service';
import { CurrentTaxRate } from 'src/app/shared/interfaces/current-tax-rate';
import {
  LegalEntitiesList,
  LegalEntity,
  XeroLinkedResponse,
} from 'src/app/shared/interfaces/legal-entity';

@Injectable({
  providedIn: 'root',
})
export class LegalEntitiesService {
  private _legalEntityId: string;
  private _legalEntityCompanyId: string;
  private _isXeroLinked: boolean;

  public constructor(private http: HttpClient) {}

  public get legalEntityId(): string {
    return this._legalEntityId;
  }

  public set legalEntityId(id: string) {
    this._legalEntityId = id;
  }

  public checkLegalEntityIdForUpdate(routeId: string): void {
    if (this.legalEntityId !== routeId) {
      this.legalEntityId = routeId;
    }
  }

  public getLegalEntities(params?: HttpParams): Observable<LegalEntitiesList> {
    return this.http.get<LegalEntitiesList>(
      `api/${this.legalEntityCompanyId}/legal_entities`,
      { params }
    );
  }

  public getLegalEntity(legalEntityId: string): Observable<LegalEntity> {
    return this.http.get<LegalEntity>(
      `api/${this.legalEntityCompanyId}/legal_entities/${legalEntityId}`
    );
  }

  public createLegalEntity(legalEntity: LegalEntity): Observable<LegalEntity> {
    return this.http.post<LegalEntity>(
      `api/${this.legalEntityCompanyId}/legal_entities`,
      legalEntity
    );
  }

  public updateLegalEntity(legalEntity: LegalEntity): Observable<LegalEntity> {
    return this.http.patch<LegalEntity>(
      `api/${this.legalEntityCompanyId}/legal_entities/${legalEntity.id}`,
      legalEntity
    );
  }

  public deleteLegalEntity(legalEntityId: string): Observable<void> {
    return this.http.delete<void>(
      `api/${this.legalEntityCompanyId}/legal_entities/${legalEntityId}`
    );
  }

  public get legalEntityCompanyId(): string {
    return this._legalEntityCompanyId;
  }

  public set legalEntityCompanyId(value: string) {
    this._legalEntityCompanyId = value;
  }

  public checkIfXeroLinked(): Observable<XeroLinkedResponse> {
    return this.http.get<XeroLinkedResponse>(
      `api/${this.legalEntityCompanyId}/legal_entities/${this.legalEntityId}/xero`
    );
  }

  public get isXeroLinked(): boolean {
    return this._isXeroLinked;
  }

  public set isXeroLinked(value: boolean) {
    this._isXeroLinked = value;
  }

  public getCurrentTaxRate(legalEntityId: string): Observable<CurrentTaxRate> {
    const company = JSON.parse(localStorage.getItem('company'));
    const params = Helpers.setParam(
      new HttpParams(),
      'company',
      company?.id.toString()
    );
    return this.http.get<CurrentTaxRate>(
      `api/legal_entities/${legalEntityId}/rates/current_rate`,
      { params: params }
    );
  }
}
