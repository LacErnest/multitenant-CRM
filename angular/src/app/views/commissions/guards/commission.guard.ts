import { Injectable } from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  UrlTree,
  Router,
} from '@angular/router';
import { Observable } from 'rxjs';
import { GlobalService } from '../../../core/services/global.service';
import { UserRole } from '../../../shared/enums/user-role.enum';

@Injectable({
  providedIn: 'root',
})
export class CommissionGuard implements CanActivate {
  public constructor(
    private globalService: GlobalService,
    private router: Router
  ) {}

  public canActivate(
    next: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree>
    | boolean
    | UrlTree {
    const allowedRoles = [
      UserRole.ADMINISTRATOR,
      UserRole.ACCOUNTANT,
      UserRole.SALES_PERSON,
      UserRole.OWNER,
      UserRole.OWNER_READ_ONLY,
    ];

    if (this.globalService.companies.find(c => allowedRoles.includes(c.role))) {
      return true;
    } else {
      return this.router.navigate(['/']).then();
    }
  }
}
