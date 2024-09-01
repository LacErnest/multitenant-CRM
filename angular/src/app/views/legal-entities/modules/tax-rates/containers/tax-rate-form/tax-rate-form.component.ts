import { Component, OnInit } from '@angular/core';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { TaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate';
import { ActivatedRoute, Router } from '@angular/router';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { TaxRatesService } from 'src/app/views/legal-entities/modules/tax-rates/tax-rates.service';
import { finalize } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { dateBeforeValidator } from 'src/app/shared/validators/date-before.validator';
import { XeroTaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/xero-tax-rate';
import { transition, trigger, useAnimation } from '@angular/animations';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { GlobalService } from '../../../../../../core/services/global.service';

@Component({
  selector: 'oz-finance-tax-rate-form',
  templateUrl: './tax-rate-form.component.html',
  styleUrls: ['./tax-rate-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class TaxRateFormComponent implements OnInit {
  public isLoading = false;
  public isXeroLinked = false;
  public taxRate: TaxRate;
  public taxRateForm: FormGroup;
  public xeroTaxRates: XeroTaxRate[];
  public salesTaxRates: XeroTaxRate[];
  public purchaseTaxRates: XeroTaxRate[];

  public constructor(
    private route: ActivatedRoute,
    private router: Router,
    private fb: FormBuilder,
    private taxRatesService: TaxRatesService,
    private toastrService: ToastrService,
    private legalEntitiesService: LegalEntitiesService,
    private globalService: GlobalService
  ) {}

  public get canSubmit(): boolean {
    return this.taxRateForm.valid && this.taxRateForm.dirty;
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initTaxRateForm();
    this.patchValueTaxRateForm();
    this.updateXeroTaxRates();
  }

  public navToXeroLink(): void {
    this.router.navigate(['../../xero'], { relativeTo: this.route }).then();
  }

  public updateXeroTaxRates(): void {
    this.updatePurchaseTaxRates();
    this.updateSalesTaxRates();
  }

  public updatePurchaseTaxRates(): void {
    this.purchaseTaxRates = this.xeroTaxRates.filter(
      r => this.taxRateForm.get('xero_sales_tax_type').value !== r.value
    );
  }

  public updateSalesTaxRates(): void {
    this.salesTaxRates = this.xeroTaxRates.filter(
      r => this.taxRateForm.get('xero_purchase_tax_type').value !== r.value
    );
  }

  public submitForm(): void {
    const val = this.taxRateForm.getRawValue();

    if (this.taxRate) {
      this.editTaxRate(val);
    } else {
      this.createTaxRate(val);
    }
  }

  private initTaxRateForm(): void {
    this.taxRateForm = this.fb.group(
      {
        id: new FormControl(undefined),
        tax_rate: new FormControl(undefined, [
          Validators.required,
          Validators.min(0),
          Validators.max(100),
        ]),
        start_date: new FormControl(undefined, [Validators.required]),
        end_date: new FormControl(undefined),
        xero_sales_tax_type: new FormControl(undefined),
        xero_purchase_tax_type: new FormControl(undefined),
      },
      { validators: [dateBeforeValidator('start_date', 'end_date')] }
    );
  }

  private patchValueTaxRateForm(): void {
    if (this.taxRate) {
      this.taxRateForm.patchValue(this.taxRate);
    }
  }

  private getResolvedData(): void {
    const { taxRate, xeroTaxRates } = this.route.snapshot.data;
    this.taxRate = taxRate;
    this.xeroTaxRates = xeroTaxRates;
    this.isXeroLinked = this.legalEntitiesService.isXeroLinked;
  }

  private createTaxRate(taxRate: TaxRate): void {
    this.isLoading = true;

    this.taxRatesService
      .createTaxRate(taxRate)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.globalService.resetCurrentCompanyTaxRate();
        this.toastrService.success('VAT rate created successfully', 'Success');
        this.router
          .navigate([`../${response.id}`], { relativeTo: this.route })
          .then();
      });
  }

  private editTaxRate(taxRate: TaxRate): void {
    this.isLoading = true;

    this.taxRatesService
      .editTaxRate(taxRate)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.taxRate = response;
        this.patchValueTaxRateForm();
        this.toastrService.success('VAT rate updated successfully', 'Success');
        this.taxRateForm.markAsPristine();
        this.globalService.resetCurrentCompanyTaxRate();
      });
  }
}
