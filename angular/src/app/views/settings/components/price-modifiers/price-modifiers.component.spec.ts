import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PriceModifiersComponent } from './price-modifiers.component';

describe('PriceModifiersComponent', () => {
  let component: PriceModifiersComponent;
  let fixture: ComponentFixture<PriceModifiersComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [PriceModifiersComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PriceModifiersComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
