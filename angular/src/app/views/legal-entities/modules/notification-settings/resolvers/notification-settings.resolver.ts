import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, Resolve } from '@angular/router';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { LegalEntityNotificationSettings } from '../interfaces/notification-settings';
import { NotificationSettingsService } from '../notification-settings.service';

@Injectable({
  providedIn: 'root',
})
export class NotificationSettingsResolver
  implements Resolve<LegalEntityNotificationSettings>
{
  public constructor(
    private notificationSettingsService: NotificationSettingsService,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public resolve(
    route: ActivatedRouteSnapshot
  ):
    | Observable<LegalEntityNotificationSettings>
    | Promise<LegalEntityNotificationSettings>
    | LegalEntityNotificationSettings {
    const routeId = route?.parent?.parent?.params?.legal_entity_id;
    this.legalEntitiesService.checkLegalEntityIdForUpdate(routeId);

    return this.notificationSettingsService.getSettings(
      this.legalEntitiesService.legalEntityId
    );
  }
}
