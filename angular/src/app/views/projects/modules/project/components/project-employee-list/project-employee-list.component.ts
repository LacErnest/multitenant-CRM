import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { DatatableContainerBase } from '../../../../../../shared/classes/datatable/datatable-container-base';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { ProjectService } from '../../project.service';
import { ToastrService } from 'ngx-toastr';
import { ProjectEmployeeService } from '../../services/project-employee.service';
import { ProjectEmployeeModalComponent } from '../project-employee-modal/project-employee-modal.component';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { GlobalService } from '../../../../../../core/services/global.service';
import { DatatableDetailConfig } from '../../../../../../shared/classes/datatable/datatable-detail-config';
import { EmployeesService } from '../../../../../employees/employees.service';
import { ConfirmModalComponent } from '../../../../../../shared/components/confirm-modal/confirm-modal.component';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { Helpers } from 'src/app/core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { HttpParams } from '@angular/common/http';

@Component({
  selector: 'oz-finance-project-employee-list',
  templateUrl: './project-employee-list.component.html',
  styleUrls: ['./project-employee-list.component.scss'],
})
export class ProjectEmployeeListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @ViewChild('projectEmployeeModal', { static: false })
  projectEmployeeModal: ProjectEmployeeModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;

  project: any;
  buttonConfig = new DatatableButtonConfig({
    filters: false,
    columns: true,
    export: this.globalService.canExport(),
    add: !this.isOwnerReadOnly(),
    delete: !this.isOwnerReadOnly(),
  });
  rowMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    showMenu: !this.isOwnerReadOnly(),
    edit: false,
    employeeAddHours: true,
  });
  public detailConfig: DatatableDetailConfig = new DatatableDetailConfig();

  protected table = 'project_employee';

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    private projectEmployeeService: ProjectEmployeeService,
    private projectService: ProjectService,
    private router: Router,
    protected route: ActivatedRoute,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
  }

  addEmployee(row?: string) {
    this.projectEmployeeModal.openModal(row).subscribe(
      result => {
        this.projectEmployeeService
          .createProjectEmployee(this.project.id, result.employee_id, result)
          .subscribe(
            () => {
              this.toastrService.success(null, 'Employee assigned');
              this.getData();
            },
            err => {
              const msg =
                err?.message ??
                'Could not assign employee. Try again or contact an administrator';
              this.toastrService.error(msg, 'Error');
            }
          );
      },
      () => {
        this.toastrService.error(
          'Could not assign employee. Try again or contact an administrator',
          'Error'
        );
      }
    );
  }

  editEmployee({ row, detailRow }) {
    this.projectEmployeeModal.openModal(row, detailRow).subscribe(
      result => {
        this.isLoading = true;
        this.projectEmployeeService
          .editProjectEmployee(this.project.id, result.employee_id, result)
          .pipe(
            finalize(() => {
              this.isLoading = false;
            })
          )
          .subscribe(() => {
            this.toastrService.success(null, 'Employee updated');
            this.getData();
          });
      },
      () => {}
    );
  }

  deleteEmployees(employees: any[]) {
    this.isLoading = true;
    this.projectEmployeeService
      .deleteProjectEmployees(
        this.project.id,
        employees.map(q => q.id.toString())
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        const msgBeginning =
          employees.length > 1 ? 'Employees have' : 'Employee has';
        this.toastrService.success(
          `${msgBeginning} been successfully removed`,
          'Success'
        );
      });
  }

  getData() {
    this.isLoading = true;
    this.projectService
      .getProject(this.project.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.project = response;
        this.rows = response.employees.rows;
      });
  }

  public deleteEmployeeHours({ row, detailRow }) {
    const result = {};
    result['month'] = detailRow.month;
    result['year'] = detailRow.year;
    result['hours'] = 0;
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to remove hours from order?')
      .subscribe(
        confirm => {
          if (confirm) {
            this.isLoading = true;
            this.projectEmployeeService
              .editProjectEmployee(this.project.id, row.id, result)
              .pipe(
                finalize(() => {
                  this.isLoading = false;
                })
              )
              .subscribe(
                () => {
                  this.toastrService.success(null, 'Employee hours removed.');
                  this.getData();
                },
                err => {
                  const msg =
                    err?.message ??
                    'Could not remove employee hours. Try again or contact an administrator';
                  this.toastrService.error(msg, 'Error');
                }
              );
          }
        },
        () => {}
      );
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    const readOnlyMode = [
      UserRole.SALES_PERSON,
      UserRole.PROJECT_MANAGER,
      UserRole.OWNER_READ_ONLY,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ].includes(role);

    const isProjectManager = role === UserRole.PROJECT_MANAGER;
    const isProjectManagerRestricted =
      role === UserRole.PROJECT_MANAGER_RESTRICTED;
    const isCurrentProjectManager = this.projectService.isCurrentProjectManager(
      this.project
    );

    if (readOnlyMode) {
      this.buttonConfig.add =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.edit =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.rowMenuConfig.delete =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }

    if (readOnlyMode) {
      this.rowMenuConfig.export = false;
    }

    if (role === UserRole.ADMINISTRATOR) {
      this.rowMenuConfig.cancel = true;
    }

    if (role === UserRole.SALES_PERSON) {
      this.rowMenuConfig.showMenu = false;
    }
  }

  private getResolvedData() {
    this.project = this.route.snapshot.parent.parent.data.project;
    this.rows = this.project.employees.rows;
    this.preferences = this.route.snapshot.data.tablePreferences;
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  public exportEmployees(): void {
    this.isLoading = true;
    const params = Helpers.setParam(
      new HttpParams(),
      'project',
      this.project.id
    );
    this.projectEmployeeService
      .exportEmployees(params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Employees');
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }
}
