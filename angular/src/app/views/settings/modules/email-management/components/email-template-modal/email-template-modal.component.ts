import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject } from 'rxjs';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { DOCUMENT } from '@angular/common';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { EmailTemplateService } from '../../email-template.service';
import { EmailTemplate } from '../../interfaces/email-template';
import { finalize } from 'rxjs/operators';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

@Component({
  selector: 'oz-finance-email-template-modal',
  templateUrl: './email-template-modal.component.html',
  styleUrls: ['./email-template-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
  ],
})
export class EmailTemplateModal implements OnInit {
  showEmailTemplateModal = false;
  showContinue = false;

  private modalSubject: Subject<any>;
  public emailTemplates: EmailTemplate[] = [];
  public isLoading = false;
  public emailTemplate: EmailTemplate;

  constructor(
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2,
    protected emailTemplateService: EmailTemplateService,
    private globalService: GlobalService,
    private sanitizer: DomSanitizer,
    private toastrService: ToastrService,
    private router: Router,
    protected route: ActivatedRoute
  ) {}

  ngOnInit(): void {}

  /**
   * To open email template modal
   * @param emailTemplate
   * @returns
   */
  public openModal(emailTemplate?: EmailTemplate): Subject<EmailTemplate> {
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showEmailTemplateModal = true;
    this.modalSubject = new Subject<EmailTemplate>();
    this.emailTemplate = emailTemplate;
    this.loaadEmailTemplates();
    return this.modalSubject;
  }

  /**
   * To close the modal
   */
  public closeModal(): void {
    this.modalSubject.complete();
    this.showEmailTemplateModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  /**
   * To assign email template to an invoice
   */
  public addEmailTemplateToInvoice(): void {
    if (!this.emailTemplate) {
      this.toastrService.error('Please select an email template.');
    } else {
      this.modalSubject.next(this.emailTemplate);
      this.modalSubject.complete();
      this.showEmailTemplateModal = false;
      this.renderer.removeClass(this._document.body, 'modal-opened');
    }
  }

  /**
   * Load all email templates
   */
  private loaadEmailTemplates(): void {
    this.isLoading = true;
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService
      .getEmailTemplates(companyId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(emailTemplates => {
        this.emailTemplates = emailTemplates;
        console.log(emailTemplates);
      });
  }

  /**
   * Check if email template is selected
   * @param emailTemplate
   * @returns
   */
  public isSelected(emailTemplate: EmailTemplate): boolean {
    return this.emailTemplate && this.emailTemplate.id === emailTemplate.id;
  }

  /**
   * Handle email template selection
   * @param emailTemplate
   */
  public handleEmailTemplateUpdate(emailTemplate: EmailTemplate): void {
    this.emailTemplate = emailTemplate;
  }

  get emailTemplateCreationUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_templates/create`;
  }

  get canCreateEmailTemplate(): boolean {
    const userRole = this.globalService.getUserRole();
    return [UserRole.OWNER, UserRole.ADMINISTRATOR].includes(userRole);
  }
}
