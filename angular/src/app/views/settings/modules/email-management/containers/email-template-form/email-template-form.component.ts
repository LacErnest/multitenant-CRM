import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject, Subscription } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { EmailEditorComponent } from 'angular-email-editor';
import { EmailTemplateService } from '../../email-template.service';
import { filter, finalize, skip } from 'rxjs/operators';
import { EmailTemplate } from '../../interfaces/email-template';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';
import { SmtpSetting } from '../../interfaces/smtp-settings';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DomSanitizer } from '@angular/platform-browser';
import { DesignTemplateModal } from '../../components/design-template-modal/design-template-modal.component';
import { DesignTemplate } from '../../interfaces/design-template';
import { DesignTemplateService } from '../../design-template.service';
@Component({
  selector: 'oz-finance-email-template-form',
  templateUrl: './email-template-form.component.html',
  styleUrls: ['./email-template-form.component.scss'],
})
export class EmailTemplateFormComponent implements OnInit, OnDestroy {
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;
  @ViewChild('designTemplateModal', { static: false })
  private designTemplateModal: DesignTemplateModal;
  isLoading = false;
  emailTemplateForm: FormGroup;
  emailTemplate: EmailTemplate;
  designTemplate: DesignTemplate;
  reminderDesignTemplates: DesignTemplate[] = [];
  private companySub: Subscription;
  private navigationSub: Subscription;
  private onDestroy$: Subject<void> = new Subject<void>();
  public readOnly = false;
  public addresses: Array<string> = [];
  public smtpSettings: SmtpSetting[] = [];

  public reminderOptions = [
    { label: 'due in', value: 1 },
    { label: 'overdue in', value: 2 },
  ];
  constructor(
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private emailTemplateService: EmailTemplateService,
    private errorHandlerService: ErrorHandlerService,
    private toastService: ToastrService,
    private designTemplateService: DesignTemplateService,
    private sanitizer: DomSanitizer
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initEmailTemplateForm();
    this.addReminder(1);
    this.patchValueEmailTemplateForm();
    this.onCurrentCompanyChanged();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
    this.companySub.unsubscribe();
    this.navigationSub.unsubscribe();
  }

  /**
   * Fetch all requested data
   */
  private getResolvedData(): void {
    this.emailTemplate = this.route.snapshot.data.emailTemplate;
    this.smtpSettings = this.route.snapshot.data.smtpSettings;
    if (this.emailTemplate) {
      this.reminderDesignTemplates =
        this.emailTemplate.reminder_design_templates;
      this.designTemplate = this.emailTemplate.design_template;
    }
  }

  /**
   * Submit email template form
   */
  submitForm(): void {
    if (this.emailTemplateForm.valid && !this.isLoading) {
      this.emailTemplate?.id
        ? this.updateEmailTemplate()
        : this.createEmailTemplate();
    }
  }

  /**
   * Update the email template
   */
  private updateEmailTemplate(): void {
    this.isLoading = true;
    const template = this.emailTemplateForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService
      .editEmailTemplate(companyId, this.emailTemplate.id, template)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.emailTemplate = response;
          this.patchValueEmailTemplateForm();
          this.toastService.success(
            'Email template has been successfully updated',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error, this.emailTemplateForm)
      );
  }

  /**
   * Create new email template
   */
  private createEmailTemplate(): void {
    this.isLoading = true;
    const template = this.emailTemplateForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService
      .createEmailTemplate(companyId, template)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        emailTemplate => {
          this.toastService.success(
            'Email template has been successfully created',
            'Success'
          );
          const route = [
            `/${companyId}/settings/email_templates/${emailTemplate.id}/edit`,
          ];
          this.router.navigate(route, { relativeTo: this.route }).then();
        },
        error => this.errorHandlerService.handle(error, this.emailTemplateForm)
      );
  }

  /**
   * Set data to email template form
   */
  private patchValueEmailTemplateForm(): void {
    if (this.emailTemplate) {
      this.emailTemplateForm.patchValue(this.emailTemplate);
    }
  }

  /**
   * Initialize the email template form rules
   */
  private initEmailTemplateForm(): void {
    this.emailTemplateForm = this.fb.group({
      title: [
        null,
        [
          Validators.required,
          Validators.minLength(5),
          Validators.maxLength(255),
        ],
      ],
      cc_addresses: [null, [Validators.required]],
      sender_id: [null, [Validators.required]],
      design_template_id: [Validators.required],
      reminder_types: this.fb.array([]),
      reminder_values: this.fb.array([]),
      reminder_ids: this.fb.array([]),
      reminder_templates: this.fb.array([]),
    });
  }

  /**
   * When use switches to an other company
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
            .navigate(['/' + value.id + '/settings/email_management/templates'])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => {
        this.getResolvedData();
        this.initEmailTemplateForm();
      });
  }

  get reminderTypes(): FormArray {
    return this.emailTemplateForm.get('reminder_types') as FormArray;
  }

  get reminderValues(): FormArray {
    return this.emailTemplateForm.get('reminder_values') as FormArray;
  }

  get reminderIds(): FormArray {
    return this.emailTemplateForm.get('reminder_ids') as FormArray;
  }

  get reminderTemplates(): FormArray {
    return this.emailTemplateForm.get('reminder_templates') as FormArray;
  }

  /**
   * Add new reminder
   * @param occurence
   */
  public addReminder(occurence = 1): void {
    for (let i = 0; i < occurence; i++) {
      this.reminderTypes.push(
        this.fb.control(this.reminderOptions[0].value, Validators.required)
      );
      this.reminderValues.push(
        this.fb.control(null, [Validators.required, Validators.min(1)])
      );
      this.reminderTemplates.push(this.fb.control(null, [Validators.required]));
      this.reminderIds.push(this.fb.control(null, []));
      this.reminderDesignTemplates.push(null);
    }
  }

  /**
   * Remove reminder by its index
   * @param index
   */
  public removeReminder(index: number): void {
    this.reminderTypes.removeAt(index);
    this.reminderValues.removeAt(index);
    this.reminderIds.removeAt(index);
    this.reminderTemplates.removeAt(index);
  }

  get emailTemplateListUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/templates`;
  }

  /**
   * Delete email template
   */
  public deleteEmailTemplate(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this email template?'
      )
      .subscribe(result => {
        if (result) {
          this.isLoading = true;
          const companyId = this.globalService.currentCompany.id;
          this.emailTemplateService
            .deleteEmailTemplate(companyId, this.emailTemplate.id)
            .pipe(finalize(() => (this.isLoading = false)))
            .subscribe(
              () => {
                this.toastService.success(
                  'Email template deleted successfully',
                  'Success'
                );
                const route = [this.emailTemplateListUrl];
                this.router.navigate(route, { relativeTo: this.route }).then();
                return;
              },
              error => this.errorHandlerService.handle(error)
            );
        }
      });
  }

  /**
   * On select new design template
   * @param param0
   */
  public selectDesignTemplate({ index }: { index: number }): void {
    this.designTemplateModal
      .openModal(this.designTemplate)
      .subscribe((designTemplate: DesignTemplate) => {
        if (index !== undefined) {
          this.reminderDesignTemplates[index] = designTemplate;
          this.reminderTemplates.controls[index].patchValue(designTemplate.id);
          this.reminderTemplates.controls[index].markAllAsTouched();
          this.reminderTemplates.controls[index].updateValueAndValidity();
        } else {
          this.designTemplate = designTemplate;
          this.emailTemplateForm.controls.design_template_id.patchValue(
            this.designTemplate.id
          );
          this.emailTemplateForm.controls.design_template_id.markAllAsTouched();
          this.emailTemplateForm.controls.design_template_id.updateValueAndValidity();
        }
      });
  }

  get designTemplateUpdatingUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/design_templates/${this.designTemplate.id}/edit`;
  }

  /**
   * On refresh design template
   * @param param0
   * @returns
   */
  refresh({ index, template }): Subscription {
    const _designTemplate = template || this.designTemplate;
    const companyId = this.globalService.currentCompany.id;
    return this.designTemplateService
      .getDesignTemplate(companyId, _designTemplate.id)
      .subscribe(template => {
        if (template) {
          this.reminderDesignTemplates[index] = template;
        } else {
          this.designTemplate = template;
        }
      });
  }

  /**
   * Get design template edition url
   * @param designTemplate
   * @returns
   */
  getDesignTemplateUpdatingUrl(designTemplate?: DesignTemplate): string {
    if (designTemplate) {
      const companyId = this.globalService.currentCompany.id;
      return `/${companyId}/settings/design_templates/${designTemplate.id}/edit`;
    }
    return '';
  }

  /**
   * Mark current settings as default
   */
  public markAsDefault(): void {
    this.isLoading = true;
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService
      .markEmailTemplateAsDefault(companyId, this.emailTemplate.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.emailTemplate = response;
          this.patchValueEmailTemplateForm();
          this.toastService.success(
            'Email template have been successfully set as default',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error)
      );
  }
}
