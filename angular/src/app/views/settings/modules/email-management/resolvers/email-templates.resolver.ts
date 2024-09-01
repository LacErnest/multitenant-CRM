import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { EmailTemplateService } from '../email-template.service';
import { EmailTemplate } from '../interfaces/email-template';
@Injectable({
  providedIn: 'root',
})
export class EmailTemplatesResolver implements Resolve<any> {
  constructor(private emailTemplateService: EmailTemplateService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<EmailTemplate[]> | Promise<EmailTemplate[]> | EmailTemplate[] {
    const companyId = route.parent?.parent?.parent.params?.company_id;
    return this.emailTemplateService.getEmailTemplates(companyId);
  }
}
