import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TaxRateFormComponent } from 'src/app/views/legal-entities/modules/tax-rates/containers/tax-rate-form/tax-rate-form.component';

describe('TaxRateFormComponent', () => {
  let component: TaxRateFormComponent;
  let fixture: ComponentFixture<TaxRateFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [TaxRateFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TaxRateFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
