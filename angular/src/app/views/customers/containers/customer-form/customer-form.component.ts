import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { HttpParams } from '@angular/common/http';
import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { concat, Observable, of, Subject, Subscription } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  finalize,
  skip,
  switchMap,
  tap,
} from 'rxjs/operators';
import { Helpers } from 'src/app/core/classes/helpers';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { emailRegEx, websiteRegEx } from 'src/app/shared/constants/regex';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { ChooseLegalEntityModalComponent } from 'src/app/views/customers/components/choose-legal-entity-modal/choose-legal-entity-modal.component';
import { CustomersService } from 'src/app/views/customers/customers.service';
import { UserRole } from '../../../../shared/enums/user-role.enum';

@Component({
  selector: 'oz-finance-customer-form',
  templateUrl: './customer-form.component.html',
  styleUrls: ['./customer-form.component.scss'],
  animations: [
    trigger('displayAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('350ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('350ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class CustomerFormComponent implements OnInit, OnDestroy {
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('chooseLegalEntityModal', { static: false })
  public chooseLegalEntityModal: ChooseLegalEntityModalComponent;

  contacts: any[];
  customer: any;
  customerForm: FormGroup;
  customerId: number;
  europeanCountries: number[];
  isLoading = false;
  isSalesSearching = false;
  salesSelect: Observable<any[]>;
  salesInput: Subject<string> = new Subject<string>();
  salesDefault: any[] = [];
  showExportMenu = false;
  showTaxNumber = false;
  exportFormats = ExportFormat;

  private companySub: Subscription;

  public constructor(
    private customersService: CustomersService,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private route: ActivatedRoute,
    private router: Router,
    private suggestService: SuggestService,
    private toastService: ToastrService
  ) {
    this.europeanCountries = this.globalService.europeanCountries;
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initCustomerForm();
    this.patchValueCustomerForm();
    this.initSalesTypeAhead();
    this.subscribeToCompanyChange();
  }

  public ngOnDestroy(): void {
    this.companySub?.unsubscribe();
  }

  public submitForm(): void {
    if (this.customerForm.valid && !this.isLoading) {
      this.customerId ? this.editCustomer() : this.createCustomer();
    }
  }

  public toggleAddress(): void {
    this.customerForm
      .get('is_same_address')
      .patchValue(!this.customerForm.get('is_same_address').value);
    this.setBillingAddress();

    this.setAddressValidators();
    this.customerForm.controls.operational_country.updateValueAndValidity();
    this.customerForm.controls.billing_country.updateValueAndValidity();
  }

  public toggleIntraCompany(): void {
    this.customerForm
      .get('intra_company')
      .patchValue(!this.customerForm.get('intra_company').value);
  }

  private setBillingAddress(): void {
    const {
      operational_addressline_1,
      operational_addressline_2,
      operational_city,
      operational_region,
      operational_postal_code,
      operational_country,
      billing_addressline_1,
      billing_addressline_2,
      billing_city,
      billing_region,
      billing_postal_code,
      billing_country,
    } = this.customerForm.controls;

    billing_addressline_1.patchValue(operational_addressline_1.value);
    billing_addressline_2.patchValue(operational_addressline_2.value);
    billing_city.patchValue(operational_city.value);
    billing_region.patchValue(operational_region.value);
    billing_postal_code.patchValue(operational_postal_code.value);
    billing_country.patchValue(operational_country.value);
  }

  public createCustomer(): void {
    if (this.customerForm.get('is_same_address').value) {
      this.setBillingAddress();
    }

    this.isLoading = true;

    this.customersService
      .createCustomer(this.customerForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.router.navigate([
            `/${this.globalService.currentCompany.id}/customers/${response.id}/edit`,
          ]);
          this.toastService.success(
            'Customer has been successfully created',
            'Success'
          );
        },
        error => {
          if (error?.message) {
            Object.keys(error?.message).forEach(key => {
              const [key_error] = error.message[key];
              if (this.customerForm.controls[key]) {
                this.customerForm.controls[key].setErrors({ error: key_error });
              }
            });
          }
          this.toastService.error('Customer has not been created', 'Error');
        }
      );
  }

  public editCustomer(): void {
    if (this.customerForm.get('is_same_address').value) {
      this.setBillingAddress();
    }

    this.isLoading = true;

    this.customersService
      .editCustomer(this.customerId.toString(), this.customerForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.toastService.success(
            'Customer has been successfully updated',
            'Success'
          );
        },
        error => {
          if (error?.message) {
            Object.keys(error?.message).forEach(key => {
              const [key_error] = error.message[key];
              if (this.customerForm.controls[key]) {
                this.customerForm.controls[key].setErrors({ error: key_error });
              }
            });
          }
          this.toastService.error('Customer has not been updated', 'Error');
        }
      );
  }

  public onBillingCountryChange({ key: billingCountryCode }): void {
    if (this.europeanCountries.includes(billingCountryCode)) {
      this.showTaxNumber = true;
    } else {
      this.customerForm.get('tax_number').patchValue(null);
      this.customerForm.get('non_vat_liable').patchValue(false);
      this.showTaxNumber = false;
    }
    this.customerForm.controls.tax_number.updateValueAndValidity();
  }

  public onOperationalCountryChange({ key: operCountryCode }): void {
    if (this.customerForm.get('is_same_address').value) {
      this.showTaxNumber = this.europeanCountries.includes(operCountryCode);
    }
  }

  public exportCustomer(): void {
    this.chooseLegalEntityModal.openCompanyLegalEntityModal();
  }

  public download(legalEntityId: string): void {
    this.downloadModal
      .openModal(
        this.customersService.exportCustomerCallback,
        [this.customer.id],
        `Customer NDA: ${this.customer.name}`,
        [ExportFormat.PDF],
        legalEntityId
      )
      .subscribe();
  }

  public toggleNonVatLiable(): void {
    this.customerForm
      .get('non_vat_liable')
      .patchValue(!this.customerForm.get('non_vat_liable').value);
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private getResolvedData(): void {
    this.customerId = this.route.snapshot.params.customer_id;
    this.customer = this.route.snapshot.data.customer;
    this.contacts = this.customer?.contacts;
    if (this.customer?.sales_person) {
      this.salesDefault = [
        {
          id: this.customer.sales_person.id,
          name: this.customer.sales_person.name,
        },
      ];
    }
  }

  private initCustomerForm(): void {
    this.customerForm = this.fb.group({
      name: new FormControl('', Validators.required),
      email: new FormControl('', [
        Validators.minLength(5),
        Validators.maxLength(256),
        Validators.pattern(emailRegEx),
      ]),
      description: new FormControl('', Validators.maxLength(250)),
      industry: new FormControl(undefined),
      status: new FormControl(1),
      tax_number: new FormControl(''),
      default_currency: new FormControl('', Validators.required),
      website: new FormControl('', Validators.pattern(websiteRegEx)),
      phone_number: new FormControl(''),
      sales_person_id: new FormControl(''),
      operational_addressline_1: new FormControl(''),
      operational_addressline_2: new FormControl(''),
      operational_city: new FormControl(''),
      operational_region: new FormControl(''),
      operational_postal_code: new FormControl(''),
      operational_country: new FormControl('', Validators.required),
      billing_addressline_1: new FormControl(''),
      billing_addressline_2: new FormControl(''),
      billing_city: new FormControl(''),
      billing_region: new FormControl(''),
      billing_postal_code: new FormControl(''),
      billing_country: new FormControl(''),
      intra_company: new FormControl(false),
      is_same_address: new FormControl(false),
      non_vat_liable: new FormControl(false),
      payment_due_date: new FormControl(null),
    });

    if (this.isOwnerReadOnly()) {
      this.customerForm.disable();
    }
  }

  private patchValueCustomerForm(): void {
    if (this.customer) {
      this.customerForm.patchValue(this.customer);
      this.customerForm
        .get('sales_person_id')
        .patchValue(this.customer.sales_person?.id);
      this.customerForm
        .get('is_same_address')
        .patchValue(
          this.customer.billing_address_id ===
            this.customer.operational_address_id
        );
      this.showTaxNumber = this.europeanCountries.includes(
        this.customer.billing_country
      );
      this.customerForm
        .get('payment_due_date')
        .patchValue(this.customer.payment_due_date);
    } else {
      this.customerForm.get('is_same_address').patchValue(true);
    }

    this.setAddressValidators();
  }

  private initSalesTypeAhead(): void {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'type', '3');

    this.salesSelect = concat(
      of(this.salesDefault), // default items
      this.salesInput.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isSalesSearching = true;
        }),
        switchMap(term =>
          this.suggestService.suggestUsers(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isSalesSearching = false;
            })
          )
        )
      )
    );
  }

  private subscribeToCompanyChange(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/customers']).then();
        }
      });
  }

  private setAddressValidators(): void {
    if (this.customerForm.get('is_same_address').value) {
      this.customerForm.controls.operational_country.setValidators(
        Validators.required
      );
      this.customerForm.controls.billing_country.setValidators(null);
      this.showTaxNumber = this.europeanCountries.includes(
        this.customerForm.get('operational_country').value
      );
    } else {
      this.customerForm.controls.operational_country.setValidators(null);
      this.customerForm.controls.billing_country.setValidators(
        Validators.required
      );
      this.showTaxNumber = this.europeanCountries.includes(
        this.customerForm.get('billing_country').value
      );
    }
  }
}
