import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { CompanyNotificationSettingsService } from '../company-notification-settings.service';
import { CompanyNotificationSettings } from '../modules/company-legal-entities/interfaces/company-notification-settings';
@Injectable({
  providedIn: 'root',
})
export class CompanyNotificationSettingsResolver implements Resolve<any> {
  constructor(private settingsService: CompanyNotificationSettingsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<CompanyNotificationSettings>
    | Promise<CompanyNotificationSettings>
    | CompanyNotificationSettings {
    const companyId = route?.parent?.params?.company_id;
    return this.settingsService.getSettings(companyId);
  }
}
