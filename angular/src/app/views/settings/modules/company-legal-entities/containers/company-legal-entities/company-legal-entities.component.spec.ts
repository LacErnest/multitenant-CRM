import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CompanyLegalEntitiesComponent } from './company-legal-entities.component';

describe('CompanyLegalEntitiesComponent', () => {
  let component: CompanyLegalEntitiesComponent;
  let fixture: ComponentFixture<CompanyLegalEntitiesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [CompanyLegalEntitiesComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CompanyLegalEntitiesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
