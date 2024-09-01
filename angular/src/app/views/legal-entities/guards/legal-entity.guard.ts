import { Injectable } from '@angular/core';
import { CanActivate, UrlTree, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class LegalEntityGuard implements CanActivate {
  public constructor(
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService,
    private router: Router
  ) {}

  public canActivate():
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree>
    | boolean
    | UrlTree {
    if (!this.legalEntitiesService.legalEntityCompanyId) {
      const companyIdWithAccess = this.globalService.companies.find(
        c => c.role === UserRole.ADMINISTRATOR || c.role === UserRole.ACCOUNTANT
      )?.id;

      if (companyIdWithAccess) {
        this.legalEntitiesService.legalEntityCompanyId = companyIdWithAccess;
      } else {
        return this.router.createUrlTree(['/dashboard']);
      }
    }

    return true;
  }
}
