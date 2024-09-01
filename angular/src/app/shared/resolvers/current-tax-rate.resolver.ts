import { Injectable } from '@angular/core';
import { Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { SharedService } from 'src/app/shared/services/shared.service';

@Injectable({
  providedIn: 'root',
})
export class CurrentTaxRateResolver implements Resolve<number> {
  public constructor(
    private globalService: GlobalService,
    private sharedService: SharedService
  ) {}

  public resolve(): Observable<number> | Promise<number> | number {
    if (this.globalService.currentCompanyTaxRate) {
      return this.globalService.currentCompanyTaxRate;
    }

    return this.sharedService
      .getCurrentTaxRate()
      .pipe(
        map(res => (this.globalService.currentCompanyTaxRate = res.tax_rate))
      );
  }
}
