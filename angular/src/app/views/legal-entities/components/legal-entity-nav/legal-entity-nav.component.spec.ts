import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LegalEntityNavComponent } from './legal-entity-nav.component';

describe('LegalEntityNavComponent', () => {
  let component: LegalEntityNavComponent;
  let fixture: ComponentFixture<LegalEntityNavComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LegalEntityNavComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LegalEntityNavComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
