import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { RentCostsComponent } from './rent-costs.component';

describe('RentCostsComponent', () => {
  let component: RentCostsComponent;
  let fixture: ComponentFixture<RentCostsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [RentCostsComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(RentCostsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
