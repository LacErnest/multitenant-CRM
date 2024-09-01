import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CommissionsSummaryComponent } from './commissions-summary.component';

describe('CommissionsSummaryComponent', () => {
  let component: CommissionsSummaryComponent;
  let fixture: ComponentFixture<CommissionsSummaryComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [CommissionsSummaryComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CommissionsSummaryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
