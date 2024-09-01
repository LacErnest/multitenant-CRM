import { Component, OnInit, ViewChild } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { emailRegEx } from 'src/app/shared/constants/regex';
import { EntityStatus } from 'src/app/shared/interfaces/entity-status';
import { Resource } from 'src/app/shared/interfaces/resource';
import { ExternalAccessService } from 'src/app/views/external-access/external-access.service';
import { ResourceExportModalComponent } from 'src/app/views/resources/components/resource-export-modal/resource-export-modal.component';
import { DownloadService } from '../../../../shared/services/download.service';
import { ResourcesService } from '../../../resources/resources.service';
import { UploadedFileNames } from '../../../../shared/interfaces/uploaded-file-names';
import { ResourceTypeNumeric } from '../../../resources/enums/resource-type.enum';
import { TablePreferences } from '../../../../shared/interfaces/table-preferences';
import { PreferenceType } from '../../../../shared/enums/preference-type.enum';
import { TablePreferenceType } from '../../../../shared/enums/table-preference-type.enum';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { ServiceList } from '../../../../shared/interfaces/service';

@Component({
  selector: 'oz-finance-resource-form-external',
  templateUrl: './resource-form-external.component.html',
  styleUrls: ['./resource-form-external.component.scss'],
})
export class ResourceFormExternalComponent implements OnInit {
  @ViewChild('exportResourceModal', { static: false })
  public exportResourceModal: ResourceExportModalComponent;

  public europeanCountries: number[];
  public isLoading = false;

  public companyID: string;
  public resource: Resource;
  public resourceForm: FormGroup;
  public resourceStatuses: EntityStatus[];
  public readonlyMode = true;

  public resourcePurchaseOrdersPreferences: TablePreferences;
  public resourceFileNames: UploadedFileNames = {
    contract_file: undefined,
  };
  public services: ServiceList;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
    add: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    showMenu: false,
  });

  public preferences = {
    columns: [
      {
        prop: 'name',
        name: 'Name',
        type: 'string',
      },
      {
        prop: 'price',
        name: 'Price',
        type: 'decimal',
      },
      {
        prop: 'price_unit',
        name: 'Unit',
        type: 'string',
      },
    ],
    all_columns: [],
    filters: [],
    sorts: [],
  };

  public constructor(
    private fb: FormBuilder,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private externalAccessService: ExternalAccessService,
    private toastrService: ToastrService,
    private enumService: EnumService,
    private downloadService: DownloadService,
    private resourcesService: ResourcesService,
    private tablePreferencesService: TablePreferencesService
  ) {
    this.europeanCountries = this.globalService.europeanCountries;
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initResourceForm();
    this.patchValueResourceForm();
    this.getServices();
    // this.getResourcePurchaseOrdersTablePreferences();

    this.resourceStatuses = this.enumService.getEnumArray('resourcestatus');
    this.resourceStatuses = this.resourceStatuses.filter(
      s => s.key === 2 || s.key === 0
    );
  }

  public submit(): void {
    if (this.resourceForm.valid && !this.isLoading) {
      this.editResource(this.resource.id, this.resourceForm.getRawValue());
    }
  }

  public editResource(resourceID: string, resource: Resource): void {
    this.isLoading = true;

    this.externalAccessService
      .editResource(this.companyID, resourceID, resource)
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
            'Update successful'
          );
        },
        error => {
          if (error?.message?.email) {
            this.resourceForm.controls.email.setErrors({ email_taken: true });
            this.toastrService.error(error?.message?.email, 'Update failed');
          } else {
            this.toastrService.error(error?.message, 'Update failed');
          }
        }
      );
  }

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
      job_title: new FormControl(undefined),
      status: new FormControl(undefined, Validators.required),
      tax_number: new FormControl(undefined),
      default_currency: new FormControl(undefined, Validators.required),
      phone_number: new FormControl(undefined),
      addressline_1: new FormControl(undefined),
      addressline_2: new FormControl(undefined),
      city: new FormControl(undefined),
      region: new FormControl(undefined),
      postal_code: new FormControl(undefined),
      country: new FormControl(undefined, Validators.required),
      contract_file: new FormControl(undefined),
    });
  }

  private patchValueResourceForm(): void {
    if (this.resource) {
      this.resourceForm.patchValue(this.resource);
      this.setFileNames();
    }
  }

  private getResolvedData(): void {
    this.resource = this.route.snapshot.data.resource;
    this.companyID = this.route.snapshot.params.company_id;
  }

  public showManagementBlock(): boolean {
    return (
      this.resource &&
      this.resourceForm.get('type').value === ResourceTypeNumeric.FREELANCER
    );
  }

  private setFileNames(): void {
    this.resourceFileNames = {
      contract_file: this.resource.contract_file,
    };
  }

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

  private getResourcePurchaseOrdersTablePreferences(): void {
    this.tablePreferencesService
      .getTablePreferences(
        PreferenceType.USERS,
        TablePreferenceType.EXTERNAL_ACCESS_PURCHASE_ORDERS
      )
      .subscribe(res => {
        this.resourcePurchaseOrdersPreferences = res;
      });
  }

  private getServices(): ServiceList {
    return (this.services = this.resource?.services);
  }
}
