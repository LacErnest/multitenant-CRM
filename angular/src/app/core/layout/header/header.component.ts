import { animate, style, transition, trigger } from '@angular/animations';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { NavigationEnd, Router } from '@angular/router';

import { Subject } from 'rxjs';
import { filter, takeUntil } from 'rxjs/operators';

import { Helpers } from 'src/app/core/classes/helpers';
import { User } from 'src/app/core/interfaces/user';
import { AuthenticationService } from 'src/app/core/services/authentication.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { AllCompanies, Company } from 'src/app/shared/interfaces/company';

@Component({
  selector: 'oz-finance-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
  animations: [
    trigger('navAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translate(-50%, .25rem)' }),
        animate(
          '200ms ease-in',
          style({ opacity: 1, transform: 'translate(-50%, 0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translate(-50%, 0)' }),
        animate(
          '150ms ease-out',
          style({ opacity: 0, transform: 'translate(-50%, .25rem)' })
        ),
      ]),
    ]),
    trigger('profileAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0)' }),
        animate('200ms ease-in', style({ opacity: 1, transform: 'scale(1)' })),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'scale(1)' }),
        animate('150ms ease-out', style({ opacity: 0, transform: 'scale(0)' })),
      ]),
    ]),
  ],
})
export class HeaderComponent implements OnInit, OnDestroy {
  public isMenuExpanded = false;
  public isProfileExpanded = false;
  public isBusinessExpanded = false;
  public isContactsExpanded = false;

  public showSettings = true;

  public company: Company | AllCompanies;
  public companies: Company[] = [];
  public user: User;
  public userRole: number;
  public userRoleEnum = UserRole;

  public companyAvatar: any;
  public companyAvatarColor: string;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private globalService: GlobalService,
    private sanitizer: DomSanitizer,
    private authenticationService: AuthenticationService,
    private router: Router
  ) {}

  public ngOnInit(): void {
    this.setValues();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  public updateCompany(company: Company | AllCompanies): void {
    if (company.id !== this.company.id) {
      this.globalService.currentCompany = company;
      this.globalService.resetCurrentCompanyTaxRate();
    }
  }

  public logout(): void {
    this.authenticationService.logout().subscribe(() => {
      this.globalService.userDetails = undefined;
    });
  }

  public showLegalEntitiesLink(): boolean {
    const allowedRoles = [UserRole.ADMINISTRATOR, UserRole.ACCOUNTANT];
    return !!this.globalService.companies.find(c =>
      allowedRoles.includes(c.role)
    );
  }

  public showCommissionsLink(): boolean {
    const allowedRoles = [
      UserRole.ADMINISTRATOR,
      UserRole.ACCOUNTANT,
      UserRole.SALES_PERSON,
      UserRole.OWNER,
      UserRole.OWNER_READ_ONLY,
    ];
    return !!this.globalService.companies.find(c =>
      allowedRoles.includes(c.role)
    );
  }

  public showTemplatesLink(): boolean {
    return this.userRole === UserRole.HUMAN_RESOURCES;
  }

  private createCompanyAvatar(company): SafeResourceUrl {
    return this.sanitizer.bypassSecurityTrustResourceUrl(
      Helpers.getSVGAvatar(
        company.id,
        company.name
          .toUpperCase()
          .split(' ')
          .map(s => s.substr(0, 1))
          .join('')
      )
    );
  }

  private checkShowSettings(): void {
    this.showSettings =
      [0, 1, 2, 6, 8].includes(this.company.role) && this.company.id !== 'all';
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.company = value;
        this.companyAvatarColor = Helpers.stringToColour(this.company.name);
        this.companyAvatar = this.createCompanyAvatar(value);
        this.isMenuExpanded = false;
        this.userRole = value.role;
        this.checkShowSettings();
      });

    this.globalService
      .getCompaniesObservable()
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.companies = value;
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => {
        this.isMenuExpanded = false;
      });
  }

  private setValues(): void {
    this.user = this.globalService.userDetails;
    this.userRole = this.globalService.getUserRole();
    this.companies = this.globalService.companies;
  }
}
