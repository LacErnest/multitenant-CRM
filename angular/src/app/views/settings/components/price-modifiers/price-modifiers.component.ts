import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject, Subscription } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { SettingsService } from 'src/app/views/settings/settings.service';
import { filter, finalize, skip } from 'rxjs/operators';

@Component({
  selector: 'oz-finance-price-modifiers-settings',
  templateUrl: './price-modifiers.component.html',
  styleUrls: ['./price-modifiers.component.scss'],
})
export class PriceModifiersComponent implements OnInit, OnDestroy {
  isLoading = false;
  modifierSettingForm: FormGroup;
  priceModifierSetting: any;
  private companySub: Subscription;
  private navigationSub: Subscription;

  private validationFields = {
    project_management_default_value: 'project_management_max_value',
    special_discount_default_value: 'special_discount_max_value',
    vat_default_value: 'vat_max_value',
    transaction_fee_default_value: 'transaction_fee_max_value',
    director_fee_default_value: 'director_fee_max_value',
  };

  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private settingsService: SettingsService,
    private toastrService: ToastrService,
    private toastService: ToastrService
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSettingsForm();
    this.subscribeChanges();
    this.onCurrentCompanyChanged();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  //#region setting values

  private getResolvedData(): void {
    this.priceModifierSetting = this.route.snapshot.data.settings || {};
  }

  //#endregion

  submitForm(): void {
    if (this.modifierSettingForm.valid && !this.isLoading) {
      this.priceModifierSetting
        ? this.updateSalesCommissionsSettings()
        : this.createSalesCommissionsSettings();
    }
  }

  private onCurrentCompanyChanged(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate(['/' + value.id + '/settings/price_modifiers'])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => {
        this.getResolvedData();
        this.initSettingsForm();
      });
  }

  /**
   * Create new sales commissions settings for current company
   * @return void
   */
  private createSalesCommissionsSettings(): void {
    this.isLoading = true;
    const settings = this.modifierSettingForm.value;
    const companyId =
      this.priceModifierSetting?.company_id ||
      this.globalService.currentCompany.id;
    this.settingsService
      .createCompanySetting(companyId, {
        ...this.priceModifierSetting,
        ...settings,
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.priceModifierSetting = response;
          this.toastService.success(
            'Sales commissions settings have been successfully created',
            'Success'
          );
        },
        error => {
          this.toastService.error(
            'Sales commissions settings have not been created',
            'Error'
          );
        }
      );
  }

  /**
   * Save sales commissions settings according to the current company
   * @return void
   */
  private updateSalesCommissionsSettings(): void {
    this.isLoading = true;
    const settings = this.modifierSettingForm.value;
    const companyId =
      this.priceModifierSetting?.company_id ||
      this.globalService.currentCompany.id;
    this.settingsService
      .editCompanySetting(companyId, {
        ...this.priceModifierSetting,
        ...settings,
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.priceModifierSetting = response;
          this.patchValueSalesCommissionsForm();
          this.toastService.success(
            'Sales commissions settings have been successfully updated',
            'Success'
          );
        },
        error => {
          this.toastService.error(
            'Sales commissions settings have not been updated',
            'Error'
          );
        }
      );
  }

  private patchValueSalesCommissionsForm() {
    if (this.priceModifierSetting) {
      this.modifierSettingForm.patchValue(this.priceModifierSetting);
    }
  }

  private initSettingsForm(): void {
    const validators = {};
    const attributes = Object.values(this.validationFields);
    attributes.push(...Object.keys(this.validationFields));
    attributes.forEach(attribute => {
      validators[attribute] = new FormControl(
        this.priceModifierSetting[attribute],
        [Validators.min(0), Validators.max(100)]
      );
    });
    this.modifierSettingForm = this.fb.group(validators);
  }

  private subscribeChanges(): void {
    Object.keys(this.validationFields).forEach(defaultValueAttribute => {
      const maxValueAttribute = this.validationFields[defaultValueAttribute];
      this.modifierSettingForm
        .get(maxValueAttribute)
        .valueChanges.subscribe(value => {
          this.modifierSettingForm.get(defaultValueAttribute).clearValidators();
          this.modifierSettingForm
            .get(defaultValueAttribute)
            .setValidators([Validators.min(0), Validators.max(value)]);
        });
    });
  }
}
