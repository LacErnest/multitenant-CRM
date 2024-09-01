import { Component, OnDestroy, OnInit } from '@angular/core';
import {
  NavigationCancel,
  NavigationEnd,
  NavigationError,
  NavigationStart,
  Router,
} from '@angular/router';
import { Observable, Subject } from 'rxjs';
import { filter, finalize, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { AllCompanies, Company } from 'src/app/shared/interfaces/company';
import { DashboardService } from 'src/app/views/dashboard/dashboard.service';
import { Analytics } from 'src/app/views/dashboard/interfaces/analytics';
import { CompanyLegalEntitiesService } from 'src/app/views/settings/modules/company-legal-entities/company-legal-entities.service';

@Component({
  selector: 'oz-finance-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
})
export class AppComponent implements OnInit, OnDestroy {
  public isLoading = true;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private router: Router,
    private globalService: GlobalService,
    private dashboardService: DashboardService,
    private companyLegalEntitiesService: CompanyLegalEntitiesService
  ) {
    // enableProdMode();
  }

  public ngOnInit(): void {
    this.initRouterSubscription();
    this.initCurrentCompanySubscription();
    this.initLoggedInSubscription();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  private initCurrentCompanySubscription(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(company => {
        this.fetchAnalytics(company);
        this.getCompanyLegalEntities(company);
      });
  }

  private initRouterSubscription(): void {
    this.router.events.pipe(takeUntil(this.onDestroy$)).subscribe(event => {
      switch (true) {
        case event instanceof NavigationStart:
          this.isLoading = true;
          break;
        case event instanceof NavigationEnd:
        case event instanceof NavigationCancel:
        case event instanceof NavigationError:
          this.isLoading = false;
          break;
      }
    });
  }

  // TODO: check double-fetching on company change
  private fetchAnalytics(company: Company | AllCompanies): void {
    if (this.globalService.isLoggedIn) {
      this.dashboardService.clearAnalyticsCache();
      let request: Observable<Analytics>;
      const params = this.dashboardService.getAnalyticsParams();

      if (company?.id === 'all') {
        request = this.dashboardService.getAllAnalytics(params);
      } else {
        request = this.dashboardService.getCompanyAnalytics(params);
      }

      request.subscribe(response => {
        this.dashboardService.analyticsSubject.next(response);
      });
    }
  }

  private getCompanyLegalEntities(company: Company | AllCompanies): void {
    if (company?.id !== 'all' && this.globalService.isLoggedIn) {
      this.isLoading = true;

      this.companyLegalEntitiesService
        .getCompanyLegalEntities()
        .pipe(
          takeUntil(this.onDestroy$),
          finalize(() => (this.isLoading = false))
        )
        .subscribe(entities => {
          this.globalService.currentLegalEntities = entities?.data ?? [];
        });
    }
  }

  private initLoggedInSubscription(): void {
    this.globalService
      .getLoggedInObservable()
      .pipe(
        filter(v => !v),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => (this.globalService.userDetails = undefined));
  }
}
