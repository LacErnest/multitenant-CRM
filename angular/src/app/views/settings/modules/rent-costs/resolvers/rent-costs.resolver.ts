import { Injectable } from '@angular/core';
import { Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { RentCostList } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost-list';
import { RentCostsService } from 'src/app/views/settings/modules/rent-costs/rent-costs.service';

@Injectable({
  providedIn: 'root',
})
export class RentCostsResolver implements Resolve<RentCostList> {
  public constructor(private rentCostsService: RentCostsService) {}

  public resolve():
    | Observable<RentCostList>
    | Promise<RentCostList>
    | RentCostList {
    return this.rentCostsService.getRentCosts();
  }
}
