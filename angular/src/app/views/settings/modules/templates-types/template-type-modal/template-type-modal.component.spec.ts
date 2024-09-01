import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TemplateTypeModalComponent } from './template-type-modal.component';

describe('TemplateTypeModalComponent', () => {
  let component: TemplateTypeModalComponent;
  let fixture: ComponentFixture<TemplateTypeModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [TemplateTypeModalComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TemplateTypeModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
