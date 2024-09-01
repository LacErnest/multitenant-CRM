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
export class TemplatesTypesResolver implements Resolve<TemplateModel[]> {
  constructor(private settingsService: SettingsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<TemplateModel[]> | Promise<TemplateModel[]> | TemplateModel[] {
    return this.settingsService.getTemplatesTypes();
  }
}
