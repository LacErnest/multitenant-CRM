import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject, Subscription } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { SmtpSettingsService } from '../../smtp-settings.service';
import { filter, finalize, skip } from 'rxjs/operators';
import { SmtpSetting } from '../../interfaces/smtp-settings';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';

@Component({
  selector: 'oz-finance-smtp-settings',
  templateUrl: './smtp-settings.component.html',
  styleUrls: ['./smtp-settings.component.scss'],
})
export class SmtpSettingsComponent implements OnInit, OnDestroy {
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;
  isLoading = false;
  smtpSettingForm: FormGroup;
  smtpSetting: SmtpSetting;
  private companySub: Subscription;
  private navigationSub: Subscription;
  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private smtpSettingsService: SmtpSettingsService,
    private errorHandlerService: ErrorHandlerService,
    private toastService: ToastrService
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSettingsForm();
    this.patchValueSmtpSettingsForm();
    this.onCurrentCompanyChanged();
  }

  public ngOnDestroy(): void {
    this.companySub.unsubscribe();
    this.navigationSub.unsubscribe();
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  /**
   * Fetch all requested data
   */
  private getResolvedData(): void {
    this.smtpSetting = this.route.snapshot.data.settings;
    console.log(this.route.snapshot.data.settings);
  }

  /**
   * Submit smtp settings form
   */
  submitForm(): void {
    if (this.smtpSettingForm.valid && !this.isLoading) {
      this.smtpSetting?.id
        ? this.updateSmtpSettings()
        : this.createSmtpSettings();
    }
  }

  /**
   * Update smtp settings
   */
  private updateSmtpSettings(): void {
    this.isLoading = true;
    const settings = this.smtpSettingForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.smtpSettingsService
      .editSmtpSetting(companyId, this.smtpSetting.id, { ...settings })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.smtpSetting = response;
          this.patchValueSmtpSettingsForm();
          this.toastService.success(
            'SMTP settings have been successfully updated',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error, this.smtpSettingForm)
      );
  }

  /**
   * Create new smtp settings
   */
  private createSmtpSettings(): void {
    this.isLoading = true;
    const settings = this.smtpSettingForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.smtpSettingsService
      .createSmtpSetting(companyId, {
        ...settings,
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.toastService.success(
            'SMTP settings have been successfully updated',
            'Success'
          );
          const route = [`../..`];
          this.router.navigate(route, { relativeTo: this.route }).then();
          return;
        },
        error => this.errorHandlerService.handle(error, this.smtpSettingForm)
      );
  }

  /**
   * Mark current settings as default
   */
  public markSmtpSettingsAsDefault(): void {
    this.isLoading = true;
    const companyId = this.globalService.currentCompany.id;
    this.smtpSettingsService
      .markSmtpSettingAsDefault(companyId, this.smtpSetting.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.smtpSetting = response;
          this.patchValueSmtpSettingsForm();
          this.toastService.success(
            'SMTP settings have been successfully set as default',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error)
      );
  }

  /**
   * Set data to the smtp settings form
   */
  private patchValueSmtpSettingsForm(): void {
    if (this.smtpSetting) {
      this.smtpSettingForm.patchValue(this.smtpSetting);
      this.updatePasswordValidators();
    }
  }

  /**
   * Update password validators rules
   */
  public updatePasswordValidators(): void {
    if (this.smtpSettingForm) {
      const passwordControl = this.smtpSettingForm.get('smtp_password');
      const password = passwordControl.value;

      if (password && password.trim().length === 1) {
        passwordControl.setValidators([
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(10),
        ]);
        passwordControl.updateValueAndValidity();
      } else if (!password || password.trim().length === 0) {
        passwordControl.clearValidators();
        passwordControl.updateValueAndValidity();
      }
    }
  }

  /**
   * Initialize the smtp settings form rules
   */
  private initSettingsForm(): void {
    this.smtpSettingForm = this.fb.group({
      smtp_host: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(100),
        ],
      ],
      smtp_port: [null, [Validators.required]],
      smtp_encryption: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(10),
        ],
      ],
      smtp_username: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(100),
        ],
      ],
      smtp_password: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(50),
        ],
      ],
      sender_email: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(200),
          Validators.email,
        ],
      ],
      sender_name: [
        null,
        [
          Validators.required,
          Validators.minLength(1),
          Validators.maxLength(100),
        ],
      ],
    });
  }

  /**
   * When user switches to another company
   */
  private onCurrentCompanyChanged(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate([
              '/' + value.id + '/settings/email_management/configurations',
            ])
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
   * Delete current smtp settings
   */
  public deleteSmtpSetting(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this smtp settings?'
      )
      .subscribe(result => {
        if (result) {
          this.isLoading = true;
          this.isLoading = true;
          const companyId = this.globalService.currentCompany.id;
          this.smtpSettingsService
            .deleteSmtpSetting(companyId, this.smtpSetting.id)
            .pipe(finalize(() => (this.isLoading = false)))
            .subscribe(
              () => {
                this.toastService.success(
                  'SMTP settings have been successfully deleted',
                  'Success'
                );
              },
              error => this.errorHandlerService.handle(error)
            );
        }
      });
  }

  get smtpSettingsUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/configurations`;
  }
}
