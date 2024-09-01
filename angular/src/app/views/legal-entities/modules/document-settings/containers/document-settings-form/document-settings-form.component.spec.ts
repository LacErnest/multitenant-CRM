import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DocumentSettingsFormComponent } from 'src/app/views/legal-entities/modules/document-settings/containers/document-settings-form/document-settings-form.component';

describe('DocumentSettingsFormComponent', () => {
  let component: DocumentSettingsFormComponent;
  let fixture: ComponentFixture<DocumentSettingsFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [DocumentSettingsFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DocumentSettingsFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
