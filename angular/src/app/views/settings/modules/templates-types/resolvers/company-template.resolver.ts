import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { SettingsService } from '../../../settings.service';
import { TemplateModel } from '../../../../../shared/interfaces/template-model';

@Injectable({
  providedIn: 'root',
})
export class CompanyTemplateResolver implements Resolve<TemplateModel> {
  public constructor(private settingsService: SettingsService) {}

  public resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<TemplateModel> | Promise<TemplateModel> | TemplateModel {
    const templateId = route.params.template_id;
    return this.settingsService.getCompanyTemplate(templateId);
  }
}
