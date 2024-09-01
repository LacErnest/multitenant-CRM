import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { DeleteResponse } from 'src/app/shared/interfaces/delete-response';
import { Service, ServiceList } from 'src/app/shared/interfaces/service';

@Injectable({
  providedIn: 'root',
})
export class ServicesService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getServices(params: HttpParams): Observable<ServiceList> {
    return this.http.get<ServiceList>(
      `api/${this.globalService.currentCompany?.id}/services`,
      { params }
    );
  }

  getService(serviceID: string): Observable<Service> {
    return this.http.get<Service>(
      `api/${this.globalService.currentCompany?.id}/services/${serviceID}`
    );
  }

  addService(service: Service): Observable<Service> {
    return this.http.post<Service>(
      `api/${this.globalService.currentCompany?.id}/services`,
      service
    );
  }

  editService(serviceID: string, service): Observable<Service> {
    return this.http.put<Service>(
      `api/${this.globalService.currentCompany?.id}/services/${serviceID}`,
      service
    );
  }

  deleteService(serviceIDs: string[]): Observable<DeleteResponse> {
    return this.http.request<DeleteResponse>(
      'delete',
      `api/${this.globalService.currentCompany?.id}/services`,
      { body: serviceIDs }
    );
  }

  getResourceServices(resourceId: string): Observable<ServiceList> {
    return this.http.get<ServiceList>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceId}/services`
    );
  }

  createResourceServices(
    resourceId: string,
    services: Service[]
  ): Observable<Service[]> {
    return this.http.post<Service[]>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceId}/services`,
      { services }
    );
  }

  editResourceService(
    resourceId: string,
    serviceID: string,
    service
  ): Observable<Service> {
    return this.http.post<Service>(
      `api/${this.globalService.currentCompany?.id}/resources/${resourceId}/services/${serviceID}`,
      service
    );
  }

  exportServices(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/services/export',
      { responseType: 'blob' }
    );
  }
}
