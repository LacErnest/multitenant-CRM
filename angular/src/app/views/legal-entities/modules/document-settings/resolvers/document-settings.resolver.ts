import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { DocumentSettingsService } from 'src/app/views/legal-entities/modules/document-settings/document-settings.service';
import { DocumentSettings } from 'src/app/views/legal-entities/modules/document-settings/interfaces/document-settings';

@Injectable({
  providedIn: 'root',
})
export class DocumentSettingsResolver implements Resolve<DocumentSettings> {
  public constructor(
    private documentSettingsService: DocumentSettingsService,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ):
    | Observable<DocumentSettings>
    | Promise<DocumentSettings>
    | DocumentSettings {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.documentSettingsService.getSettings(
      this.legalEntitiesService.legalEntityId
    );
  }
}
