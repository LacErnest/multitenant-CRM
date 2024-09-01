import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../../../../core/services/global.service';

@Injectable({
  providedIn: 'root',
})
export class ProjectEmployeeService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getProjectEmployee(projectID: string, employeeID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/employees/' +
        employeeID
    );
  }

  getEmployee(employeeID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID
    );
  }

  createProjectEmployee(
    projectID: string,
    employeeID: string,
    employee: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/employees/' +
        employeeID,
      employee
    );
  }

  editProjectEmployee(
    projectID: string,
    employeeID: string,
    employee: any
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/employees/' +
        employeeID,
      employee
    );
  }

  deleteProjectEmployees(
    projectID: string,
    employeeIDs: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/employees',
      { body: employeeIDs }
    );
  }

  exportEmployees(params?: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/employees/export',
      { responseType: 'blob', params: params }
    );
  }
}
