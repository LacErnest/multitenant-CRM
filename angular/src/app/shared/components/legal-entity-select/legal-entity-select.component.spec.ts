import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LegalEntitySelectComponent } from './legal-entity-select.component';

describe('LegalEntitySelectComponent', () => {
  let component: LegalEntitySelectComponent;
  let fixture: ComponentFixture<LegalEntitySelectComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LegalEntitySelectComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LegalEntitySelectComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
