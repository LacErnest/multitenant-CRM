import { TestBed } from '@angular/core/testing';

import { LegalEntityGuard } from './legal-entity.guard';

describe('LegalEntityGuard', () => {
  let guard: LegalEntityGuard;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    guard = TestBed.inject(LegalEntityGuard);
  });

  it('should be created', () => {
    expect(guard).toBeTruthy();
  });
});
