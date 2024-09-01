import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TemplatesTypesComponent } from './templates-types.component';

describe('TemplatesTypesComponent', () => {
  let component: TemplatesTypesComponent;
  let fixture: ComponentFixture<TemplatesTypesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [TemplatesTypesComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TemplatesTypesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
