import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EmailTemplateFormComponent } from './email-template-form.component';

describe('EmailTemplateFormComponent', () => {
  let component: EmailTemplateFormComponent;
  let fixture: ComponentFixture<EmailTemplateFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [EmailTemplateFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EmailTemplateFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
