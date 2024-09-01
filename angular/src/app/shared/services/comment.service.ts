import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';
import { Comment } from '../interfaces/comment';
@Injectable({
  providedIn: 'root',
})
export class CommentService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  getComments(
    entity: string,
    projectID: string,
    invoiceID: string
  ): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/' +
        entity +
        '/' +
        invoiceID +
        '/comments',
      {}
    );
  }

  createComment(
    entity: string,
    projectID: string,
    invoiceID: string,
    comment: Comment
  ): Observable<any> {
    return this.http.request(
      'post',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/' +
        entity +
        '/' +
        invoiceID +
        '/comments',
      { body: comment }
    );
  }

  editComment(
    entity: string,
    projectID: string,
    invoiceID: string,
    comentID: string,
    comment: Comment
  ): Observable<any> {
    return this.http.request(
      'put',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/' +
        entity +
        '/' +
        invoiceID +
        '/comments/' +
        comentID,
      { body: comment }
    );
  }

  deleteComment(
    entity: string,
    projectID: string,
    invoiceID: string,
    comentID: string[]
  ): Observable<any> {
    return this.http.request(
      'delete',
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/' +
        entity +
        '/' +
        invoiceID +
        '/comments/' +
        comentID
    );
  }
}
