import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ControlRequiredErrorComponent } from './control-required-error.component';

describe('ControlRequiredErrorComponent', () => {
  let component: ControlRequiredErrorComponent;
  let fixture: ComponentFixture<ControlRequiredErrorComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ControlRequiredErrorComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ControlRequiredErrorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
