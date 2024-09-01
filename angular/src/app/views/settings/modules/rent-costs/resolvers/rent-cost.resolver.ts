import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { RentCost } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost';
import { RentCostsService } from 'src/app/views/settings/modules/rent-costs/rent-costs.service';

@Injectable({
  providedIn: 'root',
})
export class RentCostResolver implements Resolve<RentCost> {
  public constructor(private rentCostsService: RentCostsService) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ): Observable<RentCost> | Promise<RentCost> | RentCost {
    return this.rentCostsService.getRentCost(route.params.id);
  }
}
