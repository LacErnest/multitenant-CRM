import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { ProjectInvoiceService } from '../services/project-invoice.service';

@Injectable({
  providedIn: 'root',
})
export class InvoiceResolver implements Resolve<Invoice> {
  public constructor(private projectInvoiceService: ProjectInvoiceService) {}

  public resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<Invoice> | Promise<Invoice> | Invoice {
    const id = route.params.invoice_id || route.params.resource_invoice_id;
    return this.projectInvoiceService.getProjectInvoice(
      route.parent.parent.params.project_id,
      id
    );
  }
}
