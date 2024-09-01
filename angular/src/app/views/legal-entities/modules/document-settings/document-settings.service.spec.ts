import { TestBed } from '@angular/core/testing';

import { DocumentSettingsService } from './document-settings.service';

describe('DocumentSettingsService', () => {
  let service: DocumentSettingsService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DocumentSettingsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
