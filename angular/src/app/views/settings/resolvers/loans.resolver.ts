import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { LoanList } from 'src/app/views/settings/interfaces/loan';
import { SettingsService } from 'src/app/views/settings/settings.service';

@Injectable({
  providedIn: 'root',
})
export class LoansResolver implements Resolve<any> {
  constructor(private settingsService: SettingsService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<LoanList> | Promise<LoanList> | LoanList {
    return this.settingsService.getLoans();
  }
}
