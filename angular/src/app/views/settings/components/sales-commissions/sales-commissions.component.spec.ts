import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SalesCommissionsComponent } from './sales-commissions.component';

describe('SalesCommissionsComponent', () => {
  let component: SalesCommissionsComponent;
  let fixture: ComponentFixture<SalesCommissionsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SalesCommissionsComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SalesCommissionsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
