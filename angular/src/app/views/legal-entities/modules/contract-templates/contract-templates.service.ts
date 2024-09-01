import { HttpClient, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { ContractTemplates } from 'src/app/shared/interfaces/template';
import { TemplateType } from 'src/app/shared/types/template-type';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Injectable({
  providedIn: 'root',
})
export class ContractTemplatesService {
  public constructor(
    private http: HttpClient,
    private legalEntitiesService: LegalEntitiesService
  ) {}

  public getContractTemplates(
    legalEntityId: string
  ): Observable<ContractTemplates> {
    return this.http.get<ContractTemplates>(
      `api/legal_entities/${legalEntityId}/templates`
    );
  }

  // TODO: add types
  public uploadContractTemplate(entity: string, file: any): Observable<any> {
    return this.http.patch(
      `api/legal_entities/${this.legalEntitiesService.legalEntityId}/templates/${entity}`,
      { file }
    );
  }

  public getContractTemplate(
    entity: TemplateType,
    format: ExportFormat
  ): Observable<HttpResponse<Blob>> {
    return this.http.get(
      `api/legal_entities/${this.legalEntitiesService.legalEntityId}/templates/${entity}/${format}`,
      { responseType: 'blob', observe: 'response' }
    );
  }
}
