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
import { CompanyNotificationSettings } from '../../modules/company-legal-entities/interfaces/company-notification-settings';
import { CompanyNotificationSettingsService } from '../../company-notification-settings.service';
import { emailsValidator } from 'src/app/shared/validators/emails-array.validator';
import { controlHasErrors } from 'src/app/core/classes/helpers';
import { emailRegEx } from 'src/app/shared/constants/regex';

@Component({
  selector: 'oz-finance-notification-settings',
  templateUrl: './notification-settings.component.html',
  styleUrls: ['./notification-settings.component.scss'],
})
export class NotificationSettingComponent implements OnInit, OnDestroy {
  isLoading = false;
  notificationSettingForm: FormGroup;
  notificationSetting: CompanyNotificationSettings;
  private companySub: Subscription;
  private navigationSub: Subscription;
  private companyId: string;
  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private settingsService: CompanyNotificationSettingsService,
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
    this.notificationSetting = this.route.snapshot.data.settings;
  }

  //#endregion

  submitForm(): void {
    if (this.notificationSettingForm.valid && !this.isLoading) {
      this.notificationSetting?.company_id
        ? this.updateSettings()
        : this.createSettings();
    }
  }

  /**
   * Save sales commissions settings according to the current company
   * @return void
   */
  private updateSettings(): void {
    this.isLoading = true;
    const settings = this.notificationSettingForm.value;
    const companyId =
      this.notificationSetting?.company_id ||
      this.globalService.currentCompany.id;
    this.settingsService
      .editSettings(companyId, settings)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.patchSettingsValuesForm(response);
          this.toastService.success(
            'Notification settings have been successfully updated',
            'Success'
          );
        },
        error => {
          this.toastService.error(
            'Notification settings have not been updated',
            'Error'
          );
        }
      );
  }

  /**
   * Save sales commissions settings according to the current company
   * @return void
   */
  private createSettings(): void {
    this.isLoading = true;
    const settings = this.notificationSettingForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.settingsService
      .createSettings(companyId, settings)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.patchSettingsValuesForm(response);
          this.notificationSetting = response;
          this.toastService.success(
            'Notification settings have been successfully updated',
            'Success'
          );
        },
        error => {
          this.toastService.error(
            'Notification settings have not been updated',
            'Error'
          );
        }
      );
  }

  private patchSettingsValuesForm(settings: CompanyNotificationSettings): void {
    this.notificationSetting = settings;
    this.notificationSettingForm.patchValue(this.notificationSetting);
  }

  private initSettingsForm(): void {
    this.notificationSettingForm = this.fb.group({
      cc_addresses: new FormControl(this.notificationSetting.cc_addresses, [
        Validators.required,
        emailsValidator,
      ]),
      from_address: new FormControl(this.notificationSetting.from_address, [
        Validators.required,
        Validators.pattern(emailRegEx),
      ]),
      from_name: new FormControl(this.notificationSetting.from_name, [
        Validators.required,
      ]),
      invoice_submitted_body: new FormControl(
        this.notificationSetting.invoice_submitted_body,
        [Validators.required, Validators.max(5000)]
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
            .navigate(['/' + value.id + '/settings/notifications'])
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

  public settingsControlHasErrors(controlName: string): boolean {
    return controlHasErrors(this.notificationSettingForm.controls[controlName]);
  }

  public showRequiredError(controlName: string): boolean {
    return (
      this.notificationSettingForm?.controls[controlName]?.errors?.required &&
      this.notificationSettingForm?.controls[controlName]?.dirty
    );
  }

  public showValidationError(controlName: string): boolean {
    return this.notificationSettingForm.controls[controlName]?.errors
      ?.validationErr;
  }

  public get cannotSubmit(): boolean {
    return (
      this.isLoading ||
      this.notificationSettingForm?.invalid ||
      !this.notificationSettingForm?.dirty
    );
  }
}
