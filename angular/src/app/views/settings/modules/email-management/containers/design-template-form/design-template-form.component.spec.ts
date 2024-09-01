import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DesignTemplateFormComponent } from './design-template-form.component';

describe('DesignTemplateFormComponent', () => {
  let component: DesignTemplateFormComponent;
  let fixture: ComponentFixture<DesignTemplateFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [DesignTemplateFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DesignTemplateFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
