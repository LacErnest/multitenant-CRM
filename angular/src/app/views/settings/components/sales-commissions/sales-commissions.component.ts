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
  selector: 'oz-finance-sales-commissions',
  templateUrl: './sales-commissions.component.html',
  styleUrls: ['./sales-commissions.component.scss'],
})
export class SalesCommissionsComponent implements OnInit, OnDestroy {
  isLoading = false;
  commissionSettingForm: FormGroup;
  commissionSetting: any;
  private companySub: Subscription;
  private navigationSub: Subscription;
  private companyId: string;
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
    this.onCurrentCompanyChanged();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  //#region setting values

  private getResolvedData(): void {
    this.commissionSetting = this.route.snapshot.data.settings;
  }

  //#endregion

  submitForm(): void {
    if (this.commissionSettingForm.valid && !this.isLoading) {
      this.updateSalesCommissionsSettings();
    }
  }

  /**
   * Save sales commissions settings according to the current company
   * @return void
   */
  private updateSalesCommissionsSettings(): void {
    this.isLoading = true;
    const settings = this.commissionSettingForm.value;
    const companyId =
      this.commissionSetting?.company_id ||
      this.globalService.currentCompany.id;
    this.settingsService
      .editCompanySetting(companyId, {
        ...settings,
        max_commission_percentage: parseFloat(
          settings.max_commission_percentage
        ),
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.commissionSetting = response;
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
    if (this.commissionSetting) {
      this.commissionSettingForm.patchValue(this.commissionSetting);
    }
  }

  private initSettingsForm(): void {
    this.commissionSettingForm = this.fb.group({
      max_commission_percentage: new FormControl(
        this.commissionSetting.max_commission_percentage,
        [Validators.min(0), Validators.max(100)]
      ),
      sales_person_commission_limit: new FormControl(
        this.commissionSetting.sales_person_commission_limit,
        [Validators.min(0), Validators.max(10)]
      ),
    });
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
            .navigate(['/' + value.id + '/settings/sales_commissions'])
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
}
