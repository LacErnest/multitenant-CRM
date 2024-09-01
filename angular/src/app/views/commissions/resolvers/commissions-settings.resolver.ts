import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { HttpParams } from '@angular/common/http';
import { CommissionsService } from '../commissions.service';
import { GlobalService } from '../../../core/services/global.service';
import { Helpers } from '../../../core/classes/helpers';
import { UserRole } from '../../../shared/enums/user-role.enum';

@Injectable({
  providedIn: 'root',
})
export class CommissionsSettingsResolver implements Resolve<any> {
  constructor(
    private commissionsService: CommissionsService,
    private globalService: GlobalService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.commissionsService.getCommissionSettings(
      this.globalService.currentCompany.id
    );
  }
}
