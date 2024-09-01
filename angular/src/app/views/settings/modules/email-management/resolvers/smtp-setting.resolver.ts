import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { SmtpSettingsService } from '../smtp-settings.service';
import { SmtpSetting } from '../interfaces/smtp-settings';
@Injectable({
  providedIn: 'root',
})
export class SmtpSettingResolver implements Resolve<any> {
  constructor(private settingsService: SmtpSettingsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<SmtpSetting> | Promise<SmtpSetting> | SmtpSetting {
    const companyId = route.parent?.parent?.params?.company_id;
    const smtpSettingId = route?.params?.smtp_setting_id;
    return this.settingsService.getSmtpSetting(companyId, smtpSettingId);
  }
}
