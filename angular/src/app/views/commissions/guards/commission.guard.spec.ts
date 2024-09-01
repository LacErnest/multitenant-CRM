import { TestBed } from '@angular/core/testing';

import { CommissionGuard } from './commission.guard';

describe('CommissionGuard', () => {
  let guard: CommissionGuard;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    guard = TestBed.inject(CommissionGuard);
  });

  it('should be created', () => {
    expect(guard).toBeTruthy();
  });
});
