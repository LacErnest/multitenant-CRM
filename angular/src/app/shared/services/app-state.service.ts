import { Observable } from 'rxjs';
import { Injectable } from '@angular/core';
import { LocalStorageService } from './local-storage.service';
import { GlobalService } from 'src/app/core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class AppStateService {
  constructor(
    private localStorageService: LocalStorageService,
    private globalService: GlobalService
  ) {}

  setLastDataTablePage(page: number, entity: number): void {
    const companyId = this.globalService.currentCompany?.id;
    const userId = this.globalService.userDetails?.id;
    const pages = this.localStorageService.get(userId) || {};
    if (companyId in pages) {
      pages[companyId][entity] = page;
    } else {
      pages[companyId] = {
        [entity]: page,
      };
    }
    this.localStorageService.set(userId, pages);
  }

  getLastDataTablePage(entity?: number): number {
    if (entity) {
      const companyId = this.globalService.currentCompany?.id;
      const userId = this.globalService.userDetails?.id;
      const pages = this.localStorageService.get(userId) || {};
      if (companyId in pages && entity in pages[companyId]) {
        return parseInt(pages[companyId][entity]);
      }
    }
    return null;
  }

  getLastDataTablePageObserver(): Observable<number> {
    return this.localStorageService.getObserver('lastPage');
  }
}
