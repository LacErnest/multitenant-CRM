import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InvoiceFormActionButtonsComponent } from './invoice-form-action-buttons.component';

describe('InvoiceFormActionButtonsComponent', () => {
  let component: InvoiceFormActionButtonsComponent;
  let fixture: ComponentFixture<InvoiceFormActionButtonsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [InvoiceFormActionButtonsComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(InvoiceFormActionButtonsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
