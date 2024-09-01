import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';

@Component({
  selector: 'oz-finance-settings',
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.scss'],
})
export class SettingsComponent implements OnInit, OnDestroy {
  public showSettingsNav = false;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private globalService: GlobalService,
    private router: Router
  ) {}

  public ngOnInit(): void {
    this.showSettingsNav = this.isAllowedRoute();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  private initSubscriptions(): void {
    // TODO: check similar subscriptions in other settings components and move repetitive logic to service
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(() => {
        if (!this.isAllowedRoute()) {
          this.router.navigate(['/']).then();
        }
      });
  }

  private isAllowedRoute(): boolean {
    const allowedRoles = settingsRoutesRoles.templates;
    const userRole = this.globalService.getUserRole();

    return allowedRoles.includes(userRole);
  }
}
