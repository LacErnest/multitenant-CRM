import { Injectable } from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  UrlTree,
  Router,
} from '@angular/router';
import { Observable } from 'rxjs';
import { GlobalService } from '../services/global.service';

@Injectable({
  providedIn: 'root',
})
export class CompanyGuard implements CanActivate {
  constructor(
    private globalService: GlobalService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree>
    | boolean
    | UrlTree {
    if (
      this.globalService.currentCompany?.id === 'all' &&
      this.globalService.getUserRole() === 0 &&
      (!this.globalService.companies || !route.params?.company_id)
    ) {
      return this.router.createUrlTree(['/']);
    }

    const companyInUrlIndex = this.globalService.companies.findIndex(
      company => company.id === route.params?.company_id
    );

    if (this.globalService.getUserRole() !== 0 && companyInUrlIndex === -1) {
      return this.router.createUrlTree(['/']);
    }

    if (this.globalService.getUserRole() === 0 && companyInUrlIndex === -1) {
      return this.router.createUrlTree(['/404']);
    }

    if (
      companyInUrlIndex > -1 &&
      route?.params?.company_id !== this.globalService.currentCompany?.id
    ) {
      this.globalService.currentCompany =
        this.globalService.companies[companyInUrlIndex];
    }

    return true;
  }
}
