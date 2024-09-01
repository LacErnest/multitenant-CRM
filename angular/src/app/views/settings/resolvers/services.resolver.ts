import { HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ServicesService } from 'src/app/shared/services/services.service';

@Injectable({
  providedIn: 'root',
})
export class ServicesResolver implements Resolve<any> {
  constructor(private servicesService: ServicesService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.servicesService.getServices(new HttpParams());
  }
}
