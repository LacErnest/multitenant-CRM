import {
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  ViewChild,
} from '@angular/core';
import { transition, trigger, useAnimation } from '@angular/animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  finalize,
  map,
  skip,
  switchMap,
  takeUntil,
  tap,
} from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { concat, Observable, of, Subject } from 'rxjs';
import { getCurrencySymbol } from '@angular/common';
import { DownloadService } from 'src/app/shared/services/download.service';
import { UploadedFileNames } from 'src/app/shared/interfaces/uploaded-file-names';
import { GlobalService } from 'src/app/core/services/global.service';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ResourceExportModalComponent } from 'src/app/views/resources/components/resource-export-modal/resource-export-modal.component';
import {
  emailRegEx,
  facebookRegEx,
  linkedInRegEx,
} from 'src/app/shared/constants/regex';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { EnumService } from 'src/app/core/services/enum.service';
import { EmployeesService } from 'src/app/views/employees/employees.service';
import { Employee } from 'src/app/views/employees/interfaces/employee';
import { EmployeeType } from 'src/app/views/employees/interfaces/employee-type';
import { EmployeeStatus } from 'src/app/views/employees/interfaces/employee-status';
import { CrudOperationName } from 'src/app/shared/enums/crud-operation-name.enum';
import { HttpResponse } from '@angular/common/http';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { TablePreferenceType } from '../../../../shared/enums/table-preference-type.enum';
import { TablePreferences } from '../../../../shared/interfaces/table-preferences';
import { PreferenceType } from '../../../../shared/enums/preference-type.enum';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { Service } from '../../../../shared/interfaces/service';
import { ServicesService } from '../../../../shared/services/services.service';
import { ConfirmModalComponent } from '../../../../shared/components/confirm-modal/confirm-modal.component';
import { UploadModalComponent } from '../../../../shared/components/upload-modal/upload-modal.component';

@Component({
  selector: 'oz-finance-employee-form',
  templateUrl: './employee-form.component.html',
  styleUrls: ['./employee-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class EmployeeFormComponent implements OnInit, OnDestroy {
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('exportEmployeeModal', { static: false })
  public exportEmployeeModal: ResourceExportModalComponent;
  @ViewChild('upload_file', { static: false }) public upload_file: ElementRef;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('uploadModal', { static: false })
  public uploadModal: UploadModalComponent;

  public isTypeChecked = false;
  public currencyPrefix: string;
  public isLoading = false;
  public employeeForm: FormGroup;
  public employee: Employee;
  public employeeStatuses: EmployeeStatus[];
  public employeeType: EmployeeType[];
  public exportFormats = ExportFormat;
  public employeePurchaseOrdersPreferences: TablePreferences;
  public isContractor = false;
  public employeeServicesPreferences: TablePreferences;

  public roleSelect: Observable<string[]>;
  public isRoleLoading = false;
  public roleInput: Subject<string> = new Subject<string>();
  public roleDefault: string[] = [];

  private onDestroy$: Subject<void> = new Subject<void>();
  private resourceServicesUnsaved: Service[] = [];

  public constructor(
    private downloadService: DownloadService,
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private enumService: EnumService,
    private employeesService: EmployeesService,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private tablePreferencesService: TablePreferencesService,
    private serviceService: ServicesService
  ) {}

  public get isPm(): number {
    return this.employeeForm.get('is_pm')?.value;
  }

  public get isOverhead(): number {
    return this.employeeForm.get('overhead_employee')?.value;
  }

  public get employeeHeading(): string {
    return this.employee
      ? `${this.employee.first_name} ${this.employee.last_name}`
      : 'Create employee';
  }

  public get cannotSubmit(): boolean {
    return (
      this.employeeForm.invalid || !this.employeeForm.dirty || this.isLoading
    );
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initEmployeeForm();
    this.initRoleTypeAhead();
    this.patchValueEmployeeForm();
    this.subscribeToCompanyChange();
    this.setCurrencyPrefix();

    this.employeeStatuses = this.enumService.getEnumArray('employeestatus');
    this.employeeType = this.enumService.getEnumArray('employeetype');

    if (!this.employee) {
      this.employeeStatuses = this.employeeStatuses.filter(
        s => s.key === 1 || s.key === 0
      );
      this.employeeType = this.employeeType.filter(
        s => s.key === 1 || s.key === 0
      );
    } else {
      if (this.employee?.type === 1) {
        this.isContractor = true;
        this.getEmployeePurchaseOrdersTablePreferences();
        this.getEmployeeServicesTablePreferences();
      }
    }
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  public toggleIsPmValue(): void {
    const val = this.employeeForm.get('is_pm')?.value;

    this.employeeForm.get('is_pm').patchValue(val === 1 ? 0 : 1);
    this.employeeForm.markAsDirty();
  }

  public toggleIsOverheadValue(): void {
    if (this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER) {
      this.employeeForm
        .get('overhead_employee')
        .patchValue(!this.employeeForm.get('overhead_employee').value);
      this.employeeForm.markAsDirty();
    }
  }

  public submit(): void {
    if (this.employeeForm.valid) {
      const value = this.employeeForm.getRawValue();

      if (this.employee) {
        value.id = this.employee.id;
        this.editEmployee(value);
      } else {
        this.createEmployee(value);
      }
    }
  }

  public download(): void {
    this.downloadModal
      .openModal(
        this.employeesService.exportEmployeeCallback,
        [this.employee.id],
        `Employee NDA: ${this.employee.first_name} ${this.employee.last_name}`,
        [ExportFormat.PDF]
      )
      .subscribe();
  }

  public patchUploadedFile({ controlName, fileName, uploaded }): void {
    this.employeeForm.get(controlName).patchValue(uploaded);
    this.employeeForm.get('file_name').patchValue(fileName);
    this.employeeForm.markAsDirty();
  }

  public addEmployeeFile(): void {
    this.uploadModal.openModal().subscribe(result => {
      if (result) {
        this.isLoading = true;

        this.employeesService
          .addEmployeeFile(
            this.employee.id,
            result.upload_file,
            result.file_name
          )
          .pipe(
            finalize(() => {
              this.isLoading = false;
            })
          )
          .subscribe(
            response => {
              this.employee.files = response;
              this.toastrService.success(
                'Document uploaded successfully.',
                'Success'
              );
            },
            err => {
              const msg = 'Failed to upload the document.';
              this.toastrService.error(msg, 'Error');
            }
          );
      }
    });
  }

  public downloadEmployeeFile(fileId: string, fileName: string): void {
    const callback: Observable<HttpResponse<Blob>> =
      this.employeesService.downloadEmployeeFile(this.employee.id, fileId);

    callback.subscribe((response: HttpResponse<Blob>) => {
      const file = new Blob([response.body], {
        type: response.headers.get('content-type'),
      });

      this.downloadService.createLinkForDownload(file, fileName);
    });
  }

  public deleteEmployeeFile(fileId: string): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete this document?')
      .subscribe(result => {
        if (result) {
          this.isLoading = true;

          this.employeesService
            .deleteEmployeeFile(this.employee.id, fileId)
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(
              response => {
                this.employee.files = response;
                this.toastrService.success(
                  'Document deleted successfully.',
                  'Success'
                );
              },
              err => {
                const msg = 'Failed to delete the document.';
                this.toastrService.error(msg, 'Error');
              }
            );
        }
      });
  }

  public setCurrencyPrefix(): void {
    const currencyCode = this.employeeForm.get('default_currency').value;
    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  public isProjectManager(): boolean {
    return this.globalService.getUserRole() === UserRole.PROJECT_MANAGER;
  }

  public unsavedServicesAdded(service: Service): void {
    this.resourceServicesUnsaved.push(service);
  }

  public unsavedServicesRemoved(serviceIndex: number): void {
    this.resourceServicesUnsaved.splice(serviceIndex, 1);
  }

  public typeChanged(number: number): void {
    this.isContractor = number === 1;
    if (this.isContractor) {
      this.employeeForm.controls.country.setValidators(Validators.required);
      this.employeeForm.controls.salary.setValidators(Validators.required);
      this.employeeForm.controls.working_hours.setValidators(
        Validators.required
      );
    } else {
      this.employeeForm.controls.country.setValidators(null);
      this.employeeForm.controls.salary.setValidators(null);
      this.employeeForm.controls.working_hours.setValidators(null);
    }
    this.employeeForm.controls.country.updateValueAndValidity();
    this.employeeForm.controls.salary.updateValueAndValidity();
    this.employeeForm.controls.working_hours.updateValueAndValidity();
  }

  private getResolvedData(): void {
    this.employee = this.route.snapshot.data.employee;
  }

  private initEmployeeForm(): void {
    this.employeeForm = this.fb.group({
      legal_entity_id: new FormControl(undefined, Validators.required),
      first_name: new FormControl(undefined, [
        Validators.maxLength(128),
        Validators.required,
      ]),
      last_name: new FormControl(undefined, [
        Validators.maxLength(128),
        Validators.required,
      ]),
      email: new FormControl(undefined, [
        Validators.pattern(emailRegEx),
        Validators.maxLength(256),
      ]),
      type: new FormControl(0),
      status: new FormControl(0),
      salary: new FormControl(undefined),
      working_hours: new FormControl(undefined),
      hourly_rate: new FormControl(undefined),
      phone_number: new FormControl(undefined),
      addressline_1: new FormControl(undefined),
      addressline_2: new FormControl(undefined),
      city: new FormControl(undefined),
      region: new FormControl(undefined),
      postal_code: new FormControl(undefined),
      country: new FormControl(undefined),
      facebook_profile: new FormControl(
        undefined,
        Validators.pattern(facebookRegEx)
      ),
      linked_in_profile: new FormControl(
        undefined,
        Validators.pattern(linkedInRegEx)
      ),
      started_at: new FormControl(undefined),
      upload_file: new FormControl(undefined),
      file_name: new FormControl(undefined),
      role: new FormControl(undefined),
      default_currency: new FormControl(
        this.globalService.userCurrency,
        Validators.required
      ),
      is_pm: new FormControl(0),
      overhead_employee: new FormControl(false),
    });

    if (this.isOwnerReadOnly()) {
      this.employeeForm.disable();
    }
  }

  private patchValueEmployeeForm(): void {
    if (this.employee) {
      this.employeeForm.patchValue(this.employee);
    }
  }

  private createEmployee(employee: Employee): void {
    this.isLoading = true;
    this.employeesService
      .createEmployee(employee)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.employee = response;
          if (this.resourceServicesUnsaved.length) {
            this.createEmployeeServices(
              this.employee.id,
              this.resourceServicesUnsaved
            );
          } else {
            this.goToEmployeePage();
          }

          this.toastrService.success(
            'Employee created successfully',
            'Creation successful'
          );
          this.router
            .navigate([`../${response.id}/edit`], { relativeTo: this.route })
            .then();
        },
        error => this.handleEmployeeError(error, CrudOperationName.CREATE)
      );
  }

  private createEmployeeServices(
    employeeId: string,
    services: Service[]
  ): void {
    this.isLoading = true;

    this.serviceService
      .createResourceServices(employeeId, services)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => this.goToEmployeePage());
  }

  private goToEmployeePage(): void {
    this.router
      .navigate([`../${this.employee.id}/edit`], { relativeTo: this.route })
      .then();
  }

  private editEmployee(employee: Employee): void {
    this.isLoading = true;

    this.employeesService
      .editEmployee(employee.id, employee)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.toastrService.success(
            'Employee update successfully',
            'Update successful'
          );
          this.employee = response;
          this.patchValueEmployeeForm();
          this.employeeForm.markAsPristine();
          this.employeesService.setRefreshHistory(true);
        },
        error => this.handleEmployeeError(error, CrudOperationName.UPDATE)
      );
  }

  private handleEmployeeError(error, action: CrudOperationName): void {
    const errorFields = [];

    for (const errKey in error?.message) {
      errorFields.push(errKey);
      this.employeeForm.controls[errKey]?.setErrors({
        validationErr: error?.message[errKey],
      });
    }

    const msg = `There's validation error on ${errorFields.join(', ')} field(s).`;
    // TODO: check why toaster is not shown 7 sec
    this.toastrService.error(msg, `${action} failed`, { timeOut: 7000 });
  }

  private initRoleTypeAhead(): void {
    this.roleSelect = concat(
      of(this.roleDefault),
      this.roleInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => (this.isRoleLoading = true)),
        switchMap(term =>
          this.employeesService.suggestEmployeeRole(term).pipe(
            map(res => res.suggestions),
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isRoleLoading = false;
            })
          )
        )
      )
    );
  }

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const shouldNavigateToDashboard = value?.id === 'all';
        this.router.navigate([
          shouldNavigateToDashboard ? '/' : `/${value.id}/employees`,
        ]);
      });
  }

  private getEmployeePurchaseOrdersTablePreferences(): void {
    this.tablePreferencesService
      .getTablePreferences(
        PreferenceType.USERS,
        TablePreferenceType.PROJECT_PURCHASE_ORDERS
      )
      .subscribe(res => {
        this.employeePurchaseOrdersPreferences = res;
      });
  }

  private getEmployeeServicesTablePreferences(): void {
    this.tablePreferencesService
      .getTablePreferences(
        PreferenceType.USERS,
        TablePreferenceType.RESOURCE_SERVICES
      )
      .subscribe(res => {
        this.employeeServicesPreferences = res;
      });
  }
}
