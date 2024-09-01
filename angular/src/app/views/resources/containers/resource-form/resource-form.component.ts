import { transition, trigger, useAnimation } from '@angular/animations';
import { getCurrencySymbol } from '@angular/common';
import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';

import { ToastrService } from 'ngx-toastr';

import { Subject } from 'rxjs';
import { finalize, skip, takeUntil } from 'rxjs/operators';

import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';

import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { currencyRegEx, emailRegEx } from 'src/app/shared/constants/regex';
import { CrudOperationName } from 'src/app/shared/enums/crud-operation-name.enum';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { EntityStatus } from 'src/app/shared/interfaces/entity-status';
import { Resource } from 'src/app/shared/interfaces/resource';
import { Service } from 'src/app/shared/interfaces/service';
import { TablePreferences } from 'src/app/shared/interfaces/table-preferences';
import { UploadedFileNames } from 'src/app/shared/interfaces/uploaded-file-names';
import { DownloadService } from 'src/app/shared/services/download.service';
import { ServicesService } from 'src/app/shared/services/services.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { Currency } from 'src/app/views/projects/modules/project/interfaces/currency';

import { ResourceExportModalComponent } from 'src/app/views/resources/components/resource-export-modal/resource-export-modal.component';
import { ResourceStatus } from 'src/app/views/resources/enums/resource-status.enum';
import {
  ResourceTypeNumeric,
  ResourceTypeString,
} from 'src/app/views/resources/enums/resource-type.enum';
import { ResourceTypeInSelect } from 'src/app/views/resources/interfaces/resource-type-select-value';
import { ResourcesService } from 'src/app/views/resources/resources.service';
import { showRequiredError } from 'src/app/core/classes/helpers';
import { Company } from 'src/app/shared/interfaces/company';

@Component({
  selector: 'oz-finance-resource-form',
  templateUrl: './resource-form.component.html',
  styleUrls: ['./resource-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ResourceFormComponent implements OnInit, OnDestroy {
  public europeanCountries: number[];
  public isLoading = false;
  public currencyPrefix: string;
  public resource: Resource;
  public resourceForm: FormGroup;
  public resourceStatuses: EntityStatus[];
  public showRating = false;
  public resourcePurchaseOrdersPreferences: TablePreferences;
  public resourceFileNames: UploadedFileNames = {
    contract_file: undefined,
  };
  @ViewChild('exportResourceModal', { static: false })
  private exportResourceModal: ResourceExportModalComponent;
  @ViewChild('downloadModal', { static: false })
  private downloadModal: DownloadModalComponent;
  private onDestroy$: Subject<void> = new Subject<void>();
  private resourceServicesUnsaved: Service[] = [];

  public constructor(
    private downloadService: DownloadService,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private resourcesService: ResourcesService,
    private sesourcesService: ServicesService,
    private toastrService: ToastrService,
    private enumService: EnumService,
    private tablePreferencesService: TablePreferencesService
  ) {
    this.europeanCountries = this.globalService.europeanCountries;
  }

  //#region lifecycle hooks

  public ngOnInit(): void {
    this.getResolvedData();
    this.initResourceForm();
    this.patchValueResourceForm();
    this.subscribeToCompanyChange();
    this.setDefaultValues();
    this.getResourcePurchaseOrdersTablePreferences();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  //#endregion

  //#region public form-related methods

  public submit(): void {
    if (this.resourceForm.valid && !this.isLoading) {
      let resourceData = this.resourceForm.getRawValue();
      resourceData = this.removeExistingFileFields(resourceData);

      this.resource
        ? this.editResource(this.resource.id, resourceData)
        : this.createResource(resourceData);
    }
  }

  public currencyChanged(value: Currency): void {
    const currencyCode = this.getCurrencyCode(value.key);
    this.currencyPrefix = getCurrencySymbol(currencyCode, 'narrow') + ' ';
  }

  public download(): void {
    this.exportResourceModal.openModal().subscribe(
      result => {
        this.downloadModal
          .openModal(
            this.resourcesService.exportResourceCallback,
            [this.resource.id, result.type],
            'Resource ' + result.type.toUpperCase() + ': ' + this.resource.name,
            [ExportFormat.PDF]
          )
          .subscribe(
            () => {
              //
            },
            () => {
              //
            }
          );
      },
      () => {
        //
      }
    );
  }

  public onResourceTypeChange(type: ResourceTypeInSelect): void {
    if (type.value === ResourceTypeString.SUPPLIER) {
      this.resourceForm.get('contract_file').patchValue(null);
      this.resourceFileNames.contract_file = undefined;
    }
  }

  public showResourceRequiredError(controlName: string): boolean {
    const { dirty, errors } = this.resourceForm?.controls[controlName];
    return showRequiredError(errors?.required, dirty);
  }

  //#endregion

  //#region
  public unsavedServicesAdded(service: Service): void {
    this.resourceServicesUnsaved.push(service);
  }

  public unsavedServicesRemoved(serviceIndex: number): void {
    this.resourceServicesUnsaved.splice(serviceIndex, 1);
  }

  //#endregion

  //#region public upload/download-related methods
  public downloadManagementFile(controlName: string): void {
    this.isLoading = true;

    this.resourcesService
      .downloadResourceContract(this.resource.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        const file = new Blob([response.body], {
          type: response.headers.get('content-type'),
        });
        const filename = this.resource[`${controlName}`];
        this.downloadService.createLinkForDownload(file, filename);
      });
  }

  public patchUploadedFile({ controlName, fileName, uploaded }): void {
    this.resourceForm.get(controlName).patchValue(uploaded);
    this.resourceFileNames[controlName] = fileName;
  }

  public showManagementBlock(): boolean {
    return (
      this.resource &&
      this.resourceForm.get('type').value === ResourceTypeNumeric.FREELANCER
    );
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  public toggleNonVatLiable(): void {
    this.resourceForm
      .get('non_vat_liable')
      .patchValue(!this.resourceForm.get('non_vat_liable').value);
  }

  public onCountryChange({ key: countryCode }): void {
    if (!this.europeanCountries.includes(countryCode)) {
      this.resourceForm.get('tax_number').patchValue(null);
      this.resourceForm.get('non_vat_liable').patchValue(false);
    }
    this.resourceForm.controls.tax_number.updateValueAndValidity();
  }
  //#endregion

  //#region private methods for initiating & setting values

  private initResourceForm(): void {
    this.resourceForm = this.fb.group({
      name: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      first_name: new FormControl(undefined, [Validators.maxLength(128)]),
      last_name: new FormControl(undefined, [Validators.maxLength(128)]),
      email: new FormControl(undefined, [
        Validators.maxLength(256),
        Validators.pattern(emailRegEx),
      ]),
      type: new FormControl(undefined, Validators.required),
      legal_entity_id: new FormControl(undefined, Validators.required),
      job_title: new FormControl(undefined),
      status: new FormControl(undefined, Validators.required),
      tax_number: new FormControl(undefined),
      default_currency: new FormControl(
        (<Company>this.globalService.currentCompany)?.currency,
        Validators.required
      ),
      daily_rate: new FormControl(undefined, Validators.pattern(currencyRegEx)),
      hourly_rate: new FormControl(
        undefined,
        Validators.pattern(currencyRegEx)
      ),
      phone_number: new FormControl(undefined),
      addressline_1: new FormControl(undefined),
      addressline_2: new FormControl(undefined),
      city: new FormControl(undefined),
      region: new FormControl(undefined),
      postal_code: new FormControl(undefined),
      country: new FormControl(undefined, Validators.required),
      resource_services: new FormControl([undefined]),
      contract_file: new FormControl(undefined),
      non_vat_liable: new FormControl(false),
    });

    if (this.isOwnerReadOnly()) {
      this.resourceForm.disable();
    }
  }

  private getResolvedData(): void {
    this.resource = this.route.snapshot.data.resource;

    const userRole = this.globalService.getUserRole();
    this.showRating =
      this.resource?.average_rating &&
      (userRole === UserRole.ADMINISTRATOR ||
        userRole === UserRole.OWNER ||
        userRole === UserRole.OWNER_READ_ONLY);
  }

  private setDefaultValues(): void {
    this.resourceStatuses = this.enumService.getEnumArray('resourcestatus');

    if (!this.resource) {
      this.resourceStatuses = this.resourceStatuses.filter(
        s =>
          s.key === ResourceStatus.ACTIVE || s.key === ResourceStatus.POTENTIAL
      );
    }

    const currency = this.resourceForm.get('default_currency').value;
    const currencyCode = this.getCurrencyCode(currency);
    this.currencyPrefix = getCurrencySymbol(currencyCode, 'wide') + ' ';
  }

  //#endregion

  private getCurrencyCode(currency: number): string {
    return this.enumService.getEnumMap('currencycode').get(currency);
  }

  //#region subscriptions

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$), skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/resources`]).then();
        }
      });
  }

  //#endregion

  //#region private form-related methods

  private createResource(resource: Resource): void {
    this.isLoading = true;

    this.resourcesService
      .createResource(resource)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.resource = response;

          if (this.resourceServicesUnsaved.length) {
            this.createResourceServices(
              this.resource.id,
              this.resourceServicesUnsaved
            );
          } else {
            this.goToResourcePage();
          }

          this.toastrService.success(
            'Resource created successfully',
            `${CrudOperationName.CREATE} successful`
          );
        },
        error => {
          this.handleResourceError(error, CrudOperationName.CREATE);
        }
      );
  }

  private createResourceServices(
    resourceId: string,
    services: Service[]
  ): void {
    this.isLoading = true;

    this.sesourcesService
      .createResourceServices(resourceId, services)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => this.goToResourcePage());
  }

  private goToResourcePage(): void {
    this.router
      .navigate([`../${this.resource.id}/edit`], { relativeTo: this.route })
      .then();
  }

  private handleResourceError(error, action: CrudOperationName): void {
    if (error?.message?.email) {
      this.resourceForm.controls.email.setErrors({ email_taken: true });
      this.toastrService.error(error?.message?.email, `${action} failed`);
    } else {
      this.toastrService.error(error?.message, `${action} failed`);
    }
  }

  private editResource(resourceID: string, resource: Resource): void {
    this.isLoading = true;

    this.resourcesService
      .editResource(resourceID, resource)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.resource = response;
          this.patchValueResourceForm();

          this.toastrService.success(
            'Resource updated successfully',
            `${CrudOperationName.UPDATE} successful`
          );
        },
        error => {
          this.handleResourceError(error, CrudOperationName.UPDATE);
        }
      );
  }

  private patchValueResourceForm(): void {
    if (this.resource) {
      this.resourceForm.patchValue(this.resource);
      this.setFileNames();
    }
  }

  private setFileNames(): void {
    this.resourceFileNames = {
      contract_file: this.resource.contract_file,
    };
  }

  private removeExistingFileFields(resource: Resource): Resource {
    if (
      resource.type === ResourceTypeNumeric.FREELANCER &&
      !resource.contract_file?.includes(';base64')
    ) {
      delete resource.contract_file;
    }

    return resource;
  }

  //#endregion

  private getResourcePurchaseOrdersTablePreferences(): void {
    this.tablePreferencesService
      .getTablePreferences(
        PreferenceType.USERS,
        TablePreferenceType.PROJECT_PURCHASE_ORDERS
      )
      .subscribe(res => {
        this.resourcePurchaseOrdersPreferences = res;
      });
  }
}
