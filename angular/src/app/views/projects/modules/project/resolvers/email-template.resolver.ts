import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { ProjectInvoiceService } from '../services/project-invoice.service';
import { EmailTemplate } from 'src/app/views/settings/modules/email-management/interfaces/email-template';

@Injectable({
  providedIn: 'root',
})
export class EmailTemplateResolver implements Resolve<EmailTemplate> {
  public constructor(private projectInvoiceService: ProjectInvoiceService) {}

  public resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<EmailTemplate> | Promise<EmailTemplate> | EmailTemplate {
    const id = route.params.invoice_id || route.params.resource_invoice_id;
    return this.projectInvoiceService.getProjectEmailTemplate(
      route.parent.parent.params.project_id,
      id
    );
  }
}
