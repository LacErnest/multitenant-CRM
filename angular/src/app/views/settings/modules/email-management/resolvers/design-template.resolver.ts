import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { DesignTemplate } from '../interfaces/design-template';
import { DesignTemplateService } from '../design-template.service';
@Injectable({
  providedIn: 'root',
})
export class DesignTemplateResolver implements Resolve<any> {
  constructor(private designTemplateService: DesignTemplateService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<DesignTemplate> | Promise<DesignTemplate> | DesignTemplate {
    const companyId = route.parent?.params?.company_id;
    const designTemplateId = route?.params?.design_template_id;
    return this.designTemplateService.getDesignTemplate(
      companyId,
      designTemplateId
    );
  }
}
