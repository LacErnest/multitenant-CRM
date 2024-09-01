import { Component, OnInit } from '@angular/core';
import { Subject } from 'rxjs';
import { skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { SettingsRoutesEnum } from 'src/app/views/settings/enums/settings-routes.enum';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';

@Component({
  selector: 'oz-finance-settings-nav',
  templateUrl: './settings-nav.component.html',
  styleUrls: ['./settings-nav.component.scss'],
})
export class SettingsNavComponent implements OnInit {
  public settingsRoutes = SettingsRoutesEnum;

  private userRole: number;
  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(private globalService: GlobalService) {}

  public ngOnInit(): void {
    this.userRole = this.globalService.getUserRole();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public showSuperAdminSettingsLink(): boolean {
    return this.globalService.userDetails?.super_user;
  }

  public showSettingsLink(link: SettingsRoutesEnum): boolean {
    return settingsRoutesRoles[link].includes(this.userRole);
  }

  public showNotificationSettingsLink(link: SettingsRoutesEnum): boolean {
    return settingsRoutesRoles[link].includes(this.userRole);
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => (this.userRole = value.role));
  }
}
