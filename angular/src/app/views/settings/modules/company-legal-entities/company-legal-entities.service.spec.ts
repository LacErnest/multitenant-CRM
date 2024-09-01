import { TestBed } from '@angular/core/testing';

import { CompanyLegalEntitiesService } from './company-legal-entities.service';

describe('CompanyLegalEntitiesService', () => {
  let service: CompanyLegalEntitiesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CompanyLegalEntitiesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
