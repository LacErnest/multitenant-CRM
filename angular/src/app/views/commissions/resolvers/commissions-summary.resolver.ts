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
export class CommissionsSummaryResolver implements Resolve<any> {
  constructor(
    private commissionsService: CommissionsService,
    private globalService: GlobalService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(route.queryParams)) {
      if (key !== 'type') {
        params = Helpers.setParam(params, key, value);
      }
    }

    if (this.globalService.getUserRole() === UserRole.SALES_PERSON) {
      params = Helpers.setParam(
        params,
        'sales_person_id',
        this.globalService.userDetails.id
      );
    }

    return this.commissionsService.getCommissionSummary(params);
  }
}
