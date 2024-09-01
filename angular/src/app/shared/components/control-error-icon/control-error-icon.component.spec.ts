import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ControlErrorIconComponent } from './control-error-icon.component';

describe('ControlErrorIconComponent', () => {
  let component: ControlErrorIconComponent;
  let fixture: ComponentFixture<ControlErrorIconComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ControlErrorIconComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ControlErrorIconComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
