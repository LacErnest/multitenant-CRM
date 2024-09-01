import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { EUROPEAN_COUNTRIES } from 'src/app/shared/constants/european-countries';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { AllCompanies, Company } from 'src/app/shared/interfaces/company';
import { User } from 'src/app/core/interfaces/user';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { environment } from 'src/environments/environment';
import {
  CompanyLegalEntitiesList,
  CompanyLegalEntity,
  LegalEntity,
} from 'src/app/shared/interfaces/legal-entity';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';

@Injectable({
  providedIn: 'root',
})
export class GlobalService {
  public europeanCountries = EUROPEAN_COUNTRIES; // TODO: remove and change usage to constant

  private _taxRate: number;

  private _companies = new BehaviorSubject<Company[]>(
    JSON.parse(localStorage.getItem('companies')) ?? []
  );

  public constructor(private legalEntitiesService: LegalEntitiesService) {}

  public get companies(): Company[] {
    return this._companies.value;
  }

  public set companies(value: Company[]) {
    localStorage.setItem('companies', JSON.stringify(value));
    this._companies.next(value);
  }

  private _currentCompany: BehaviorSubject<Company | AllCompanies> =
    new BehaviorSubject<Company | AllCompanies>(
      JSON.parse(localStorage.getItem('company'))
    );

  public get currentCompany(): Company | AllCompanies {
    return this._currentCompany.value;
  }

  public set currentCompany(value: Company | AllCompanies) {
    localStorage.setItem('company', JSON.stringify(value));
    this._currentCompany.next(value);
  }

  private _currentLegalEntities: BehaviorSubject<CompanyLegalEntity[]> =
    new BehaviorSubject<CompanyLegalEntity[]>([]);

  public get currentLegalEntities(): CompanyLegalEntity[] {
    return this._currentLegalEntities.value;
  }

  public set currentLegalEntities(value: CompanyLegalEntity[]) {
    this._currentLegalEntities.next(value);
  }

  private _userDetails = new BehaviorSubject<User>(
    JSON.parse(localStorage.getItem('user') || null)
  );

  public get userDetails(): User {
    return this._userDetails.value;
  }

  public set userDetails(value: User) {
    if (value) {
      localStorage.setItem('user', JSON.stringify(value));
    } else {
      localStorage.removeItem('user');
      localStorage.removeItem('access_token');
      localStorage.removeItem('analytics_filters');
      localStorage.removeItem('companies');
      localStorage.removeItem('company');
      localStorage.removeItem('xero_state');
      localStorage.removeItem('xero_legal_entity_id');
      localStorage.removeItem('super_user');
      this.legalEntitiesService.legalEntityCompanyId = null;
      this.currentLegalEntities = [];
    }

    this._userDetails.next(value);
  }

  private _isLoggedIn = new BehaviorSubject<boolean>(
    localStorage.getItem('access_token') !== null || false
  );

  public get isLoggedIn(): boolean {
    return this._isLoggedIn.value;
  }

  public set isLoggedIn(value: boolean) {
    this._isLoggedIn.next(value);
  }

  public getLoggedInObservable(): BehaviorSubject<boolean> {
    return this._isLoggedIn;
  }

  public get userCurrency(): number {
    return (<Company>this.currentCompany)?.currency;
  }

  public getCurrentCompanyObservable(): BehaviorSubject<
    Company | AllCompanies
  > {
    return this._currentCompany;
  }

  public getCurrentLegalEntitiesObservable(): BehaviorSubject<
    CompanyLegalEntity[]
  > {
    return this._currentLegalEntities;
  }

  public getCompaniesObservable(): BehaviorSubject<Company[]> {
    return this._companies;
  }

  public getUserRole(): number {
    return this?.currentCompany?.role;
  }

  public canExport(preference?: TablePreferenceType): boolean {
    const allowedRoles = [
      UserRole.ACCOUNTANT,
      UserRole.ADMINISTRATOR,
      UserRole.OWNER,
      UserRole.SUPER_ADMIN,
    ];
    switch (preference) {
      case TablePreferenceType.EMPLOYEES:
      case TablePreferenceType.RESOURCES:
        allowedRoles.push(UserRole.HUMAN_RESOURCES);
        break;
    }

    return allowedRoles.includes(this.currentCompany?.role);
  }

  public get currentCompanyTaxRate(): number {
    return this._taxRate;
  }

  public set currentCompanyTaxRate(value: number) {
    this._taxRate = value;
  }

  public resetCurrentCompanyTaxRate(): void {
    this.currentCompanyTaxRate = null;
  }

  public refreshCompany(): void {
    this._currentCompany.next(this.currentCompany);
  }

  public getCompanySalesSupportPercentage(): number {
    return (<Company>this.currentCompany)?.sales_support_percentage;
  }

  public set setCompanySalesSupportPercentage(value: number) {
    (<Company>this.currentCompany).sales_support_percentage = value;
  }
}
