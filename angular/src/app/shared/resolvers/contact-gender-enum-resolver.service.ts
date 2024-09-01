import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable, of } from 'rxjs';
import { EnumService } from '../../core/services/enum.service';

@Injectable({
  providedIn: 'root',
})
export class ContactGenderEnumResolver implements Resolve<any> {
  constructor(private enumService: EnumService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    if (!this.enumService.hasEnum('contactgendertypes')) {
      return this.enumService
        .getEnums(['contact_gender.types'])
        .toPromise()
        .then(result => {
          this.enumService.setEnums(result);
        });
    } else {
      return of(true);
    }
  }
}
