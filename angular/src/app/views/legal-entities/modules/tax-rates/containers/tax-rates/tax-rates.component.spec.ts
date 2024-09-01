import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TaxRatesComponent } from 'src/app/views/legal-entities/modules/tax-rates/containers/tax-rates/tax-rates.component';

describe('TaxRatesComponent', () => {
  let component: TaxRatesComponent;
  let fixture: ComponentFixture<TaxRatesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [TaxRatesComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TaxRatesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
