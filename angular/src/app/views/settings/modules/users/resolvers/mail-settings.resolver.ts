import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { UsersService } from '../users.service';
import { GlobalService } from '../../../../../core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class MailSettingsResolver implements Resolve<any> {
  constructor(
    private usersService: UsersService,
    private globalService: GlobalService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    const user_id = route.params.user_id;
    return user_id === this.globalService.userDetails.id &&
      this.globalService.getUserRole() === 1
      ? this.usersService.getMailSettings()
      : null;
  }
}
