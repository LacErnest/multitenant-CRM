import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { RentCostFormComponent } from './rent-cost-form.component';

describe('RentCostFormComponent', () => {
  let component: RentCostFormComponent;
  let fixture: ComponentFixture<RentCostFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [RentCostFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(RentCostFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
