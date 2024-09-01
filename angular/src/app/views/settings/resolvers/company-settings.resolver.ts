import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { SettingsService } from 'src/app/views/settings/settings.service';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';
@Injectable({
  providedIn: 'root',
})
export class CompanySettingsResolver implements Resolve<any> {
  constructor(
    private legalEntitiesService: LegalEntitiesService,
    private settingsService: SettingsService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<CompanySetting> | Promise<CompanySetting> | CompanySetting {
    const companyId =
      route?.params.company_id || route?.parent?.params?.company_id;
    return this.settingsService.getSettings(companyId);
  }
}
