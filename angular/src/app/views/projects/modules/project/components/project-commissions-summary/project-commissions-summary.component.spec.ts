import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ProjectCommissionsSummaryComponent } from './project-commissions-summary.component';

describe('ProjectCommissionsSummaryComponent', () => {
  let component: ProjectCommissionsSummaryComponent;
  let fixture: ComponentFixture<ProjectCommissionsSummaryComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ProjectCommissionsSummaryComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ProjectCommissionsSummaryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
