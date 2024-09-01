import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  Router,
  RouterStateSnapshot,
  UrlTree,
} from '@angular/router';
import { Observable } from 'rxjs';
import { GlobalService } from '../services/global.service';
import { UserRole } from '../../shared/enums/user-role.enum';
import { validateEarnoutQuery } from '../../shared/validators/earnout-query.validator';
import { ToastrService } from 'ngx-toastr';

@Injectable({
  providedIn: 'root',
})
export class EarnoutGuard implements CanActivate {
  protected availableRoles = [
    UserRole.OWNER,
    UserRole.ADMINISTRATOR,
    UserRole.ACCOUNTANT,
    UserRole.OWNER_READ_ONLY,
  ];

  constructor(
    private globalService: GlobalService,
    private router: Router,
    private toastrService: ToastrService
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree>
    | boolean
    | UrlTree {
    const redirectToDashboard = this.router.createUrlTree(['dashboard']);
    const queryParams = route.queryParams;

    if (
      Object.keys(queryParams).length !== 0 &&
      validateEarnoutQuery(queryParams) === true
    ) {
      const currentCompanyId = this.globalService.currentCompany?.id;
      const userHasPermission =
        this.availableRoles.includes(this.globalService.getUserRole()) === true;

      if (userHasPermission && currentCompanyId !== 'all') {
        return true;
      } else {
        this.toastrService.warning('You need to select a company!', 'Warning');
        return redirectToDashboard;
      }
    }

    this.toastrService.error('queryParams is invalid!', 'Error');
    return redirectToDashboard;
  }
}
