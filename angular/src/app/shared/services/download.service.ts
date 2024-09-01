import { DOCUMENT } from '@angular/common';
import { Inject, Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class DownloadService {
  constructor(@Inject(DOCUMENT) private document: Document) {}

  public createLinkForDownload(file: Blob, filename: string): void {
    const link = this.document.createElement('a');
    this.document.body.appendChild(link);
    link.setAttribute('href', URL.createObjectURL(file));
    link.setAttribute('download', filename);
    link.click();
    this.document.body.removeChild(link);
  }
}
