import { Injectable } from '@angular/core';
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  UrlTree,
  Router,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ExternalAccessService } from '../../views/external-access/external-access.service';

@Injectable({
  providedIn: 'root',
})
export class ExternalAccessGuard implements CanActivate {
  constructor(
    private externalAccessService: ExternalAccessService,
    private router: Router
  ) {}

  canActivate(
    next: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ):
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree>
    | boolean
    | UrlTree {
    if (next.params.token) {
      this.externalAccessService.userToken = next.params.token;
      return true;
    } else {
      return this.router.createUrlTree(['404']);
    }
  }
}
