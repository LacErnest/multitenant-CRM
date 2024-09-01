import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { Resource } from 'src/app/shared/interfaces/resource';
import { ResourceList } from 'src/app/views/invoices/interfaces/resource-list';

type ResourceExportType = 'nda' | 'contractor' | 'freelancer';

@Injectable({
  providedIn: 'root',
})
export class ResourcesService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public get exportResourceCallback() {
    return this.exportResource.bind(this);
  }

  public getResources(params: HttpParams): Observable<ResourceList> {
    return this.http.get<ResourceList>(
      `api/${this.globalService.currentCompany?.id}/resources`,
      { params }
    );
  }

  public getResource(resourceID: string): Observable<Resource> {
    return this.http.get<Resource>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceID}`
    );
  }

  public createResource(resource: Resource): Observable<Resource> {
    return this.http.post<Resource>(
      `api/${this.globalService.currentCompany?.id}/resources`,
      resource
    );
  }

  public editResource(
    resourceID: string,
    resource: Resource
  ): Observable<Resource> {
    return this.http.put<Resource>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceID}`,
      resource
    );
  }

  // TODO: add type here and below
  public deleteResources(resourceIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      `api${this.globalService.currentCompany?.id}/resources`,
      { body: resourceIDs }
    );
  }

  public importResource(file: string): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/resources/import`,
      { file }
    );
  }

  public exportResource(
    format: ExportFormat,
    resourceID: string,
    type: ResourceExportType
  ): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceID}/export/${type}/${format}`,
      { responseType: 'blob' }
    );
  }

  public exportResources(): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/resources/export`,
      { responseType: 'blob' }
    );
  }

  public finalizeResourceImport(matches: any): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/resources/import/finalize`,
      matches
    );
  }

  public downloadResourceContract(
    resourceID: string
  ): Observable<HttpResponse<Blob>> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceID}/download/contract`,
      { responseType: 'blob', observe: 'response' }
    );
  }
}
