import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LegalEntityFormComponent } from 'src/app/views/legal-entities/components/legal-entity-form/legal-entity-form.component';

describe('LegalEntityFormComponent', () => {
  let component: LegalEntityFormComponent;
  let fixture: ComponentFixture<LegalEntityFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LegalEntityFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LegalEntityFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
