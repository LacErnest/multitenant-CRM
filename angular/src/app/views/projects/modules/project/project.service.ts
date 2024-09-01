import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, Subject } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';

@Injectable({
  providedIn: 'root',
})
export class ProjectService {
  readonly project: Subject<Project> = new Subject<Project>();

  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public set currentProject(value) {
    this.project.next(value);
  }

  public getProject(projectID: string): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/projects/' + projectID
    );
  }

  public getCustomers(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/customers',
      { params }
    );
  }

  public getCustomerCurrency(customerID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany.id +
        '/customers/currency/' +
        customerID
    );
  }

  public get exportResourceInvoiceCallback() {
    return this.exportResourceInvoice.bind(this);
  }

  public exportResourceInvoice(
    format: string,
    resourceID: string,
    purchaseOrderID: string,
    invoiceID: string
  ): Observable<Blob> {
    return this.http.get(
      `api/${this.globalService.currentCompany.id}/resources/${resourceID}/purchase_orders/${purchaseOrderID}/invoices/${invoiceID}/download`,
      { responseType: 'blob' }
    );
  }

  public isCurrentProjectManager(project): boolean {
    return project?.project_manager_id === this.globalService.userDetails.id;
  }
}
