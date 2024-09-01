import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ProjectPurchaseOrderService } from '../services/project-purchase-order.service';

@Injectable({
  providedIn: 'root',
})
export class PurchaseOrderResolver implements Resolve<any> {
  constructor(
    private projectPurchaseOrderServiceService: ProjectPurchaseOrderService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.projectPurchaseOrderServiceService.getProjectPurchaseOrder(
      route.parent.parent.params.project_id,
      route.params.purchase_order_id
    );
  }
}
