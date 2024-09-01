import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LegalEntityWrapperComponent } from './legal-entity-wrapper.component';

describe('LegalEntityWrapperComponent', () => {
  let component: LegalEntityWrapperComponent;
  let fixture: ComponentFixture<LegalEntityWrapperComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LegalEntityWrapperComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LegalEntityWrapperComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
