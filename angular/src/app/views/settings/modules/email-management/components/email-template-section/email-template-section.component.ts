import {
  Component,
  EventEmitter,
  Input,
  OnDestroy,
  OnInit,
  Output,
  ViewChild,
} from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Observable, Subject, Subscription } from 'rxjs';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { EmailTemplateModal } from '../email-template-modal/email-template-modal.component';
import { EmailTemplateService } from '../../email-template.service';
import { EmailTemplate } from '../../interfaces/email-template';

@Component({
  selector: 'oz-finance-email-template-section',
  templateUrl: './email-template-section.component.html',
  styleUrls: ['./email-template-section.component.scss'],
})
export class EmailTemplateSection implements OnInit, OnDestroy {
  @ViewChild('emailTemplateModal', { static: false })
  private emailTemplateModal: EmailTemplateModal;
  @Output() public onEmailTemplateUpdated: EventEmitter<EmailTemplate> =
    new EventEmitter<EmailTemplate>();
  @Input() emailTemplate: EmailTemplate;
  @Input() readOnly: boolean;
  @Input() emailTemplateSelect: Observable<boolean>;
  private navigationSub: Subscription;
  private companySub: Subscription;
  public hideEmailTemplatePreview = true;

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService,
    protected emailTemplateService: EmailTemplateService
  ) {
    //
  }

  ngOnInit(): void {
    if (this.emailTemplateSelect) {
      this.emailTemplateSelect.subscribe(status => {
        if (status) {
          this.selectEmailTemplate();
        }
      });
    }
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  /**
   * On select email template
   */
  public selectEmailTemplate(): void {
    this.emailTemplateModal
      .openModal(this.emailTemplate)
      .subscribe((emailTemplate: EmailTemplate) => {
        this.emailTemplate = emailTemplate;
        this.onEmailTemplateUpdated.emit(emailTemplate);
      });
  }

  get emailTemplateUpdatingUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_templates/${this.emailTemplate.id}/edit`;
  }

  get canCreateEmailTemplate(): boolean {
    const userRole = this.globalService.getUserRole();
    return [UserRole.OWNER, UserRole.ADMINISTRATOR].includes(userRole);
  }
}
