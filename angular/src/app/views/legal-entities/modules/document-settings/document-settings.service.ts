import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { DocumentSettings } from 'src/app/views/legal-entities/modules/document-settings/interfaces/document-settings';

@Injectable({
  providedIn: 'root',
})
export class DocumentSettingsService {
  public constructor(
    private http: HttpClient,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public getSettings(legalEntityId: string): Observable<DocumentSettings> {
    return this.http.get<DocumentSettings>(
      `api/legal_entities/${legalEntityId}/settings`
    );
  }

  public editSettings(
    settings: DocumentSettings
  ): Observable<DocumentSettings> {
    return this.http.patch<DocumentSettings>(
      `api/legal_entities/${this.legalEntitiesService.legalEntityId}/settings`,
      settings
    );
  }
}
