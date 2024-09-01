import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../../../core/services/global.service';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class UsersService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getUsers(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/users',
      { params }
    );
  }

  getUser(userID: string): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/users/' + userID
    );
  }

  createUser(user: any): Observable<any> {
    return this.http.post(
      'api/' + this.globalService.currentCompany?.id + '/users',
      user
    );
  }

  editUser(userID: string, user: any): Observable<any> {
    return this.http.put(
      'api/' + this.globalService.currentCompany?.id + '/users/' + userID,
      user
    );
  }

  deleteUsers(userIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' + this.globalService.currentCompany?.id + '/users',
      { body: userIDs }
    );
  }

  toggleUserStatus(userID: string): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/users/' +
        userID +
        '/status',
      {}
    );
  }

  getMailSettings(): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/users/mail_preferences'
    );
  }

  editMailSettings(mailSettings: any): Observable<any> {
    return this.http.put(
      'api/' +
        this.globalService.currentCompany?.id +
        '/users/mail_preferences',
      mailSettings
    );
  }

  resendLink(user: any): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/users/' +
        user.id +
        '/resend_link',
      user
    );
  }
}
