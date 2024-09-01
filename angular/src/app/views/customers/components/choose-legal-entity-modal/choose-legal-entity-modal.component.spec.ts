import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ChooseLegalEntityModalComponent } from './choose-legal-entity-modal.component';

describe('ChooseLegalEntityModalComponent', () => {
  let component: ChooseLegalEntityModalComponent;
  let fixture: ComponentFixture<ChooseLegalEntityModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ChooseLegalEntityModalComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ChooseLegalEntityModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
