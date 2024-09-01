import { TestBed } from '@angular/core/testing';

import { DownloadService } from 'src/app/shared/services/download.service';

describe('DownloadServiceService', () => {
  let service: DownloadService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DownloadService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
