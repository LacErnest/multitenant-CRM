import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable, of } from 'rxjs';
import { SmtpSettingsService } from '../smtp-settings.service';
import { SmtpSetting } from '../interfaces/smtp-settings';
@Injectable({
  providedIn: 'root',
})
export class SmtpSettingsResolver implements Resolve<any> {
  constructor(private settingsService: SmtpSettingsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<SmtpSetting[]> | Promise<SmtpSetting[]> | SmtpSetting[] {
    let companyId = null;
    let parent = route.parent;

    // Traverse up the route tree to find company_id
    while (parent) {
      if (parent.params?.company_id) {
        companyId = parent.params.company_id;
        break;
      }
      parent = parent.parent;
    }
    if (companyId) {
      return this.settingsService.getSmtpSettings(companyId);
    }
    return of([]);
  }
}
