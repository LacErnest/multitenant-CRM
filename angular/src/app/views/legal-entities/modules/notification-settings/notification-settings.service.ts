import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { LegalEntityNotificationSettings } from 'src/app/views/legal-entities/modules/notification-settings/interfaces/notification-settings';

@Injectable({
  providedIn: 'root',
})
export class NotificationSettingsService {
  public constructor(
    private http: HttpClient,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public getSettings(
    legalEntityId: string
  ): Observable<LegalEntityNotificationSettings> {
    return this.http.get<LegalEntityNotificationSettings>(
      `api/legal_entities/${legalEntityId}/notifications/settings`
    );
  }

  public createSettings(
    settings: LegalEntityNotificationSettings
  ): Observable<LegalEntityNotificationSettings> {
    return this.http.post<LegalEntityNotificationSettings>(
      `api/legal_entities/${this.legalEntitiesService.legalEntityId}/notifications/settings`,
      settings
    );
  }

  public editSettings(
    settings: LegalEntityNotificationSettings
  ): Observable<LegalEntityNotificationSettings> {
    return this.http.patch<LegalEntityNotificationSettings>(
      `api/legal_entities/${this.legalEntitiesService.legalEntityId}/notifications/settings`,
      settings
    );
  }
}
