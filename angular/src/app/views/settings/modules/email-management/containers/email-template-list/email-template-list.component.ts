import { Component, OnDestroy, OnInit } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter, skip } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { Subscription } from 'rxjs';
import {
  DatatableMenuConfig,
  DatatableMenuStyle,
} from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { transition, trigger, useAnimation } from '@angular/animations';
import { EmailTemplateService } from '../../email-template.service';

@Component({
  selector: 'oz-finance-email-template-list',
  templateUrl: './email-template-list.component.html',
  styleUrls: ['./email-template-list.component.scss'],
  animations: [
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('alertAnimation', [
      transition(':enter', useAnimation(alertEnterAnimation)),
      transition(':leave', useAnimation(alertLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class EmailTemplateListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  emailTemplates: { data: any[]; count: number };
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    export: false,
    add: false,
    import: false,
    refresh: false,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    delete: false,
    style: DatatableMenuStyle.INLINE,
  });
  protected table = 'email_templates';
  private navigationSub: Subscription;
  private companySub: Subscription;
  public emailTemplateDisabledGlobally: boolean;

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService,
    private emailTemplateService: EmailTemplateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
    this.getGloballyDisabledStatus();
    const userRole = this.globalService.getUserRole();
    this.buttonConfig.add =
      this.buttonConfig.delete =
      this.buttonConfig.edit =
        userRole === 0 || userRole === 1;
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData(): void {
    //
  }

  /**
   * On edit email template
   * @param id
   */
  editTemplate(id: string): void {
    this.router.navigate([this.emailTemplateEditionPartialUrl + id + '/edit'], {
      relativeTo: this.route,
    });
  }

  /**
   * Fetch all requested data
   */
  private getResolvedData(): void {
    const { tablePreferences, email_templates } = this.route.snapshot.data;
    this.emailTemplates = {
      data: email_templates,
      count: email_templates.length,
    };
    this.preferences = tablePreferences;
  }

  /**
   * When user switches to another company
   */
  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate(['/' + value.id + '/settings/email_management/templates'])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  get emailTemplateCreationUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_templates/create`;
  }

  get emailTemplateEditionPartialUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_templates/`;
  }

  /**
   * Toggle email template globally disabled status
   */
  public toggleGloballyDisabledStatus(): void {
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService.toggleGloballyDisabledStatus(companyId).subscribe(
      data => {
        this.emailTemplateDisabledGlobally = data;
        const status = data ? 'disabled' : 'enabled';
        this.toastService.success(
          `Email template have been successfully ${status} globally`,
          'Success'
        );
      },
      () => {
        const status = this.emailTemplateDisabledGlobally
          ? 'disabled'
          : 'enabled';
        this.emailTemplateDisabledGlobally =
          !this.emailTemplateDisabledGlobally;
        this.toastService.error(
          `Email template cannot be ${status} globally`,
          'Error'
        );
      }
    );
  }

  /**
   * Get email template globally disabled status
   */
  public getGloballyDisabledStatus(): void {
    const companyId = this.globalService.currentCompany.id;
    this.emailTemplateService
      .getGloballyDisabledStatus(companyId)
      .subscribe(data => {
        this.emailTemplateDisabledGlobally = data;
      });
  }
}
