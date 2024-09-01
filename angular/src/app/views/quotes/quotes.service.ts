import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { GlobalService } from '../../core/services/global.service';
import { Observable } from 'rxjs';
import { ExportFormat } from '../../shared/enums/export.format';
import { DownloadCallback } from '../../shared/components/download-modal/download-modal.component';

@Injectable({
  providedIn: 'root',
})
export class QuotesService {
  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  get exportQuoteCallback(): DownloadCallback {
    return this.exportQuote.bind(this);
  }

  getQuotes(params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/quotes',
      { params }
    );
  }

  deleteQuotes(quotesIDs: string[]): Observable<any> {
    return this.http.request(
      'delete',
      'api/' + this.globalService.currentCompany?.id + '/quotes',
      { body: quotesIDs }
    );
  }

  cloneQuote(
    projectID: string,
    quoteID: string,
    destination_id: any
  ): Observable<any> {
    return this.http.post(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/clone',
      { destination_id }
    );
  }

  exportQuote(
    format: ExportFormat,
    projectID: string,
    quoteID: string,
    templateId: string
  ): Observable<Blob> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes/' +
        quoteID +
        '/export/' +
        templateId +
        '/' +
        format,
      { responseType: 'blob' }
    );
  }

  exportQuotes(params?: HttpParams): Observable<Blob> {
    return this.http.get(
      'api/' + this.globalService.currentCompany?.id + '/quotes/export',
      { responseType: 'blob', params: params }
    );
  }

  getProjectQuotes(projectID: string, params: HttpParams): Observable<any> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/projects/' +
        projectID +
        '/quotes',
      { params }
    );
  }
}
