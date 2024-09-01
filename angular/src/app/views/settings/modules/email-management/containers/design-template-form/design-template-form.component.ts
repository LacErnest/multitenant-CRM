import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject, Subscription } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { EmailEditorComponent } from 'angular-email-editor';
import { filter, finalize, skip } from 'rxjs/operators';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';
import { SmtpSetting } from '../../interfaces/smtp-settings';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DomSanitizer } from '@angular/platform-browser';
import { DesignTemplateService } from '../../design-template.service';
import { DesignTemplate } from '../../interfaces/design-template';
@Component({
  selector: 'oz-finance-design-template-form',
  templateUrl: './design-template-form.component.html',
  styleUrls: ['./design-template-form.component.scss'],
})
export class DesignTemplateFormComponent implements OnInit, OnDestroy {
  @ViewChild('emailEditor')
  private emailEditor: EmailEditorComponent;
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;
  isLoading = false;
  designTemplateForm: FormGroup;
  designTemplate: DesignTemplate;
  private companySub: Subscription;
  private navigationSub: Subscription;
  private onDestroy$: Subject<void> = new Subject<void>();
  public readOnly = false;
  public addresses: Array<string> = [];
  public smtpSettings: SmtpSetting[] = [];
  public designTemplateContent;
  public appearance = {
    theme: 'modern_light',
    backgroundColor: '#333',
  };

  constructor(
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private designTemplateService: DesignTemplateService,
    private errorHandlerService: ErrorHandlerService,
    private toastService: ToastrService,
    private sanitizer: DomSanitizer
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initDesignTemplateForm();
    this.patchValueDesignTemplateForm();
    this.onCurrentCompanyChanged();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  private getResolvedData(): void {
    this.designTemplate = this.route.snapshot.data.designTemplate;
  }

  submitForm(): void {
    if (this.designTemplateForm.valid && !this.isLoading) {
      this.emailEditor.saveDesign(design => {
        this.emailEditor.exportHtml(html => {
          this.saveChanges(design, html);
        });
      });
    }
  }

  saveChanges(design: any, html: any): void {
    this.designTemplate?.id
      ? this.updateDesignTemplate(JSON.stringify(design), JSON.stringify(html))
      : this.createDesignTemplate(JSON.stringify(design), JSON.stringify(html));
  }

  /**
   * Called when the email editor is created
   */
  editorLoaded(event): void {
    if (this.designTemplate?.design) {
      const designTemplateContent = JSON.parse(this.designTemplate.design);
      this.emailEditor.editor.loadDesign(designTemplateContent || {});
    }
    const companyId = this.globalService.currentCompany.id;
    this.emailEditor.editor.registerCallback('image', (file, done) => {
      const data = new FormData();
      data.append('image', file.attachments[0]);
      this.designTemplateService
        .uploadDesignTemplateImage(companyId, data)
        .subscribe(data => {
          done({ progress: 100, url: data.url });
        });
    });
  }

  /**
   * On update existing design template
   * @param design
   * @param html
   */
  private updateDesignTemplate(design: string, html: string): void {
    this.isLoading = true;
    const template = this.designTemplateForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.designTemplateService
      .editDesignTemplate(companyId, this.designTemplate.id, {
        ...template,
        html,
        design,
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.designTemplate = response;
          this.patchValueDesignTemplateForm();
          this.toastService.success(
            'Design template has been successfully updated',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error, this.designTemplateForm)
      );
  }

  /**
   * To create a new design template
   * @param design
   * @param html
   */
  private createDesignTemplate(design: string, html: string): void {
    this.isLoading = true;
    const template = this.designTemplateForm.value;
    const companyId = this.globalService.currentCompany.id;
    this.designTemplateService
      .createDesignTemplate(companyId, {
        ...template,
        html,
        design,
      })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        designTemplate => {
          this.toastService.success(
            'Design template has been successfully created',
            'Success'
          );
          const route = [
            `/${companyId}/settings/design_templates/${designTemplate.id}/edit`,
          ];
          this.router.navigate(route, { relativeTo: this.route }).then();
        },
        error => this.errorHandlerService.handle(error, this.designTemplateForm)
      );
  }

  /**
   * Initialize the design template from with values
   */
  private patchValueDesignTemplateForm(): void {
    if (this.designTemplate) {
      this.designTemplateForm.patchValue(this.designTemplate);
    }
  }

  /**
   * Initialize the design template from rules
   */
  private initDesignTemplateForm(): void {
    this.designTemplateForm = this.fb.group({
      title: [
        null,
        [
          Validators.required,
          Validators.minLength(5),
          Validators.maxLength(255),
        ],
      ],
      subject: [
        null,
        [
          Validators.required,
          Validators.minLength(5),
          Validators.maxLength(255),
        ],
      ],
    });
  }

  /**
   * When user switches to an other company
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
              '/' + value.id + '/settings/email_management/design_templates',
            ])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => {
        this.getResolvedData();
        this.initDesignTemplateForm();
      });
  }

  get designTemplateListUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/design_templates`;
  }

  /**
   * Delete current design template and redirect to design templates view
   */
  public deleteDesignTemplate(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this design template?'
      )
      .subscribe(result => {
        if (result) {
          this.isLoading = true;
          const companyId = this.globalService.currentCompany.id;
          this.designTemplateService
            .deleteDesignTemplate(companyId, this.designTemplate.id)
            .pipe(finalize(() => (this.isLoading = false)))
            .subscribe(
              () => {
                this.toastService.success(
                  'Design template deleted successfully',
                  'Success'
                );
                const route = [this.designTemplateListUrl];
                this.router.navigate(route, { relativeTo: this.route }).then();
                return;
              },
              error => this.errorHandlerService.handle(error)
            );
        }
      });
  }

  editorOption(): any {
    const companyId = this.globalService.currentCompany.id;
    const uri = location.protocol + location.host;
    return {
      appearance: {
        theme: 'dark',
        panels: {
          tools: {
            dock: 'left',
          },
        },
      },
      features: {
        imageEditor: {
          enabled: true,
        },
        stockImages: {
          enabled: false,
        },
        userUploads: false,
      },
      tools: {
        image: {
          enabled: true,
          properties: {
            src: {
              value: {
                url: `${uri}/api/${companyId}/settings/design-templates/uploads`,
                width: 500,
                height: 100,
              },
            },
          },
        },
      },
    };
  }
}
