import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PartialPaidModalComponent } from './partial-paid-modal.component';

describe('PartialPaidModalComponent', () => {
  let component: PartialPaidModalComponent;
  let fixture: ComponentFixture<PartialPaidModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [PartialPaidModalComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PartialPaidModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
