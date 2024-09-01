import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ProjectService } from '../project.service';
import { ProjectOrderService } from '../services/project-order.service';

@Injectable({
  providedIn: 'root',
})
export class OrderResolver implements Resolve<any> {
  constructor(private projectOrderService: ProjectOrderService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.projectOrderService.getProjectOrder(
      route.parent.parent.params.project_id,
      route.params.order_id
    );
  }
}
