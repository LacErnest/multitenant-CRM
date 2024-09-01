import { Component, OnDestroy, OnInit } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter, skip } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { Subscription } from 'rxjs';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-email-management',
  templateUrl: './email-management.component.html',
  styleUrls: ['./email-management.component.scss'],
})
export class EmailManagementComponent implements OnInit, OnDestroy {
  users: { data: any[]; count: number };

  protected table = 'users';
  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService
  ) {
    //
  }

  ngOnInit(): void {
    this.initSubscriptions();
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate(['/' + value.id + '/settings/email_management'])
            .then();
        }
      });
  }

  /**
   * Check if current tab is active according to the current link
   * @param tab
   * @returns
   */
  public isActiveTab(tab: string): boolean {
    const currentUrl = this.router.url;

    return currentUrl.split('/').includes(tab);
  }

  get emailTemplateListUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/templates`;
  }

  get smtpSettingsListUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/configurations`;
  }

  get emailDesignListUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/email_management/design_templates`;
  }
}
