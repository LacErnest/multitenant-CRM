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
import { DesignTemplateService } from '../../design-template.service';
import { DesignTemplate } from '../../interfaces/design-template';
import { finalize } from 'rxjs/operators';

@Component({
  selector: 'oz-finance-design-template-modal',
  templateUrl: './design-template-modal.component.html',
  styleUrls: ['./design-template-modal.component.scss'],
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
export class DesignTemplateModal implements OnInit {
  showDesignTemplateModal = false;
  showContinue = false;

  private modalSubject: Subject<any>;
  public designTemplates: DesignTemplate[] = [];
  public isLoading = false;
  public designTemplate?: DesignTemplate;

  constructor(
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2,
    protected designTemplateService: DesignTemplateService,
    private globalService: GlobalService,
    private toastrService: ToastrService,
    private router: Router,
    protected route: ActivatedRoute
  ) {}

  ngOnInit(): void {}

  /**
   * To open the modal dialog
   * @param designTemplate
   * @returns
   */
  public openModal(designTemplate?: DesignTemplate): Subject<DesignTemplate> {
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showDesignTemplateModal = true;
    this.modalSubject = new Subject<DesignTemplate>();
    this.designTemplate = designTemplate;
    this.loaadDesignTemplates();
    return this.modalSubject;
  }

  /**
   * To close the modal
   */
  public closeModal(): void {
    if (this.modalSubject) {
      this.modalSubject.complete();
    }
    this.showDesignTemplateModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  /**
   * To affect selected design template to an existing invoice
   */
  public addDesignTemplateToInvoice(): void {
    if (!this.designTemplate) {
      this.toastrService.error('Please select an email template.');
    } else {
      this.modalSubject.next(this.designTemplate);
      this.modalSubject.complete();
      this.showDesignTemplateModal = false;
      this.renderer.removeClass(this._document.body, 'modal-opened');
    }
  }

  /**
   * To create new design template
   * @returns
   */
  public createNewDesignTemplate(): void {
    this.router
      .navigate([this.emailTemplateCreationUrl], { relativeTo: this.route })
      .then();
    return;
  }

  /**
   * Load design templates on modal opening
   */
  private loaadDesignTemplates(): void {
    this.isLoading = true;
    const companyId = this.globalService.currentCompany.id;
    this.designTemplateService
      .getDesignTemplates(companyId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(designTemplates => {
        this.designTemplates = designTemplates;
      });
  }

  /**
   * Check if design template is selected
   * @param designTemplate
   * @returns
   */
  public isSelected(designTemplate: DesignTemplate): boolean {
    return (
      this.designTemplate !== null &&
      this.designTemplate !== undefined &&
      this.designTemplate.id === designTemplate.id
    );
  }

  /**
   * handle design template selection update
   * @param designTemplate
   */
  public handleDesignTemplateUpdate(designTemplate: DesignTemplate): void {
    this.designTemplate = designTemplate;
  }

  get emailTemplateCreationUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/design_templates/create`;
  }

  get canCreateDesignTemplate(): boolean {
    const userRole = this.globalService.getUserRole();
    return [UserRole.OWNER, UserRole.ADMINISTRATOR].includes(userRole);
  }
}
