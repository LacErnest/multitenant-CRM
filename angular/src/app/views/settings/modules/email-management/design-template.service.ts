import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DesignTemplateStatus } from './interfaces/design-template-status';
import { DesignTemplate } from './interfaces/design-template';

@Injectable({
  providedIn: 'root',
})
export class DesignTemplateService {
  public constructor(private http: HttpClient) {}

  public getDesignTemplates(companyId: string): Observable<DesignTemplate[]> {
    return this.http.get<DesignTemplate[]>(
      `api/${companyId}/settings/design-templates`
    );
  }

  public getDesignTemplate(
    companyId: string,
    id: string
  ): Observable<DesignTemplate> {
    return this.http.get<DesignTemplate>(
      `api/${companyId}/settings/design-templates/${id}`
    );
  }

  public createDesignTemplate(
    companyId: string,
    emailDesign: DesignTemplate
  ): Observable<DesignTemplate> {
    return this.http.post<DesignTemplate>(
      `api/${companyId}/settings/design-templates`,
      emailDesign
    );
  }

  public editDesignTemplate(
    companyId: string,
    id: string,
    emailDesign: DesignTemplate
  ): Observable<DesignTemplate> {
    return this.http.patch<DesignTemplate>(
      `api/${companyId}/settings/design-templates/${id}`,
      emailDesign
    );
  }

  public deleteDesignTemplate(
    companyId: string,
    id: string
  ): Observable<DesignTemplateStatus> {
    return this.http.delete<DesignTemplateStatus>(
      `api/${companyId}/settings/design-templates/${id}`
    );
  }

  public uploadDesignTemplateImage(
    companyId: string,
    data: FormData
  ): Observable<any> {
    return this.http.post<any>(
      `api/${companyId}/settings/design-templates/uploads`,
      data
    );
  }
}
