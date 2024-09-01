import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Observable, Subject } from 'rxjs';
import { EmployeeRoleSuggestions } from 'src/app/views/employees/interfaces/employee-role-suggestions';
import { Employee } from 'src/app/views/employees/interfaces/employee';
import { ImportMatches } from 'src/app/views/customers/customers.service';
import { DownloadCallback } from 'src/app/shared/components/download-modal/download-modal.component';
import { GlobalService } from 'src/app/core/services/global.service';
import { EmployeeHistory } from './interfaces/employee-history';

@Injectable({
  providedIn: 'root',
})
export class EmployeesService {
  private refreshHistory = new Subject<boolean>();

  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public get exportEmployeeCallback(): DownloadCallback {
    return this.exportEmployee.bind(this);
  }

  public getEmployees(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/employees',
      { params }
    );
  }

  public getEmployee(employeeID: string): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID
    );
  }

  public createEmployee(employee: Employee): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/employees',
      employee
    );
  }

  public editEmployee(employeeID: string, employee: Employee): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID,
      employee
    );
  }

  public importEmployee(file: string): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/employees/import',
      { file }
    );
  }

  public finalizeImportEmployee(employee: {
    id: string;
    matches: ImportMatches;
  }): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/import/finalize',
      employee
    );
  }

  // TODO: add type here and for APIs which use DownloadModalComponent + change DownloadCallback type (to use `response.body`)
  public exportEmployee(format: string, employeeID: string): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/employees/${employeeID}/export/employee/${format}`,
      { responseType: 'blob' }
    );
  }

  public exportEmployees(): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/employees/export`,
      { responseType: 'blob' }
    );
  }

  public addEmployeeFile(
    employeeID: string,
    file: string,
    file_name: string
  ): Observable<any> {
    return this.http.post(
      `api/${this.globalService.currentCompany?.id}/employees/${employeeID}/upload`,
      { file, file_name }
    );
  }

  public downloadEmployeeFile(
    employeeID: string,
    fileId: string
  ): Observable<HttpResponse<Blob>> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/employees/${employeeID}/download/file/${fileId}`,
      { responseType: 'blob', observe: 'response' }
    );
  }

  public deleteEmployeeFile(
    employeeID: string,
    fileId: string
  ): Observable<any> {
    return this.http.delete(
      `api/${this.globalService.currentCompany?.id}/employees/${employeeID}/delete_file/${fileId}`
    );
  }

  public suggestEmployeeRole(
    value: string
  ): Observable<EmployeeRoleSuggestions> {
    return this.http.get<EmployeeRoleSuggestions>(
      `api/${this.globalService.currentCompany?.id}/employees/suggest_role/${value}`
    );
  }

  public getActiveEmployees(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/employees/active',
      { params: params }
    );
  }

  public editEmployeeHours(request): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/employees/edit_hours',
      request
    );
  }

  public deleteEmployeeHours(request): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/delete_hours',
      { body: request }
    );
  }

  public getEmployeeHistory(
    employeeID: string,
    params?: HttpParams
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID +
        '/histories',
      { params }
    );
  }

  public createEmployeeHistory(
    employeeID: string,
    history: EmployeeHistory
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID +
        '/histories',
      history
    );
  }

  public editEmployeeHistory(
    employeeID: string,
    historyId: string,
    history: EmployeeHistory
  ): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID +
        '/histories/' +
        historyId,
      history
    );
  }

  public deleteEmployeeHistory(
    employeeID: string,
    historyId: string
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/employees/' +
        employeeID +
        '/histories/' +
        historyId
    );
  }

  public setRefreshHistory(bool: boolean): void {
    this.refreshHistory.next(bool);
  }

  public getRefreshHistory(): Observable<boolean> {
    return this.refreshHistory.asObservable();
  }
}
