import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';

@Injectable({
  providedIn: 'root',
})
export class SharedProjectEntityService {
  constructor(
    private globalService: GlobalService,
    private http: HttpClient
  ) {}

  deleteEntityItems(
    projectID: string,
    entity: ProjectEntityEnum,
    entityID: string,
    itemsIds: string[]
  ): Observable<void> {
    return this.http.request<void>(
      'delete',
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/${entity}/${entityID}/items`,
      { body: { items: itemsIds } }
    );
  }

  /**
   * Update order sharing state
   * @param projectID
   * @param entityID
   * @param isMaster
   * @returns
   */
  shareOrder(
    projectID: string,
    entityID: string,
    isMaster: boolean
  ): Observable<boolean> {
    return this.http.request<boolean>(
      'put',
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/orders/${entityID}/share`,
      { body: { master: isMaster } }
    );
  }

  /**
   * Check sharing permissions for an order
   * @param projectID
   * @param entityID
   * @returns
   */
  checkSharingPermissions(
    projectID: string,
    entityID: string
  ): Observable<boolean> {
    return this.http.request<boolean>(
      'get',
      `api/${this.globalService.currentCompany?.id}/projects/${projectID}/orders/${entityID}/check-sharing-permissions`
    );
  }
}
