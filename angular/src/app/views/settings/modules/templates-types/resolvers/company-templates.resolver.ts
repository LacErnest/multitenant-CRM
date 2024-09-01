import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { SettingsService } from '../../../settings.service';
import { CompanyTemplates } from '../../../../../shared/interfaces/template';

@Injectable({
  providedIn: 'root',
})
export class CompanyTemplatesResolver implements Resolve<CompanyTemplates> {
  public constructor(private settingsService: SettingsService) {}

  public resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<CompanyTemplates>
    | Promise<CompanyTemplates>
    | CompanyTemplates {
    const templateId = route.params.template_id;
    return this.settingsService.getCompanyTemplates(templateId);
  }
}
