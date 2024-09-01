import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LegalEntitiesListComponent } from './legal-entities-list.component';

describe('LegalEntitiesListComponent', () => {
  let component: LegalEntitiesListComponent;
  let fixture: ComponentFixture<LegalEntitiesListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LegalEntitiesListComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LegalEntitiesListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
