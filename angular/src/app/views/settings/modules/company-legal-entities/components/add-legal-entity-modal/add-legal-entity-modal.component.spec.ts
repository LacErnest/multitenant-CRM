import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddLegalEntityModalComponent } from './add-legal-entity-modal.component';

describe('AddLegalEntityModalComponent', () => {
  let component: AddLegalEntityModalComponent;
  let fixture: ComponentFixture<AddLegalEntityModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [AddLegalEntityModalComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddLegalEntityModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
