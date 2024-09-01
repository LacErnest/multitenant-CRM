import { TestBed } from '@angular/core/testing';

import { LegalEntitiesService } from './legal-entities.service';

describe('LegalEntitiesService', () => {
  let service: LegalEntitiesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(LegalEntitiesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
