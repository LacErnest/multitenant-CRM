import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ContractTemplatesComponent } from './contract-templates.component';

describe('ContractTemplatesComponent', () => {
  let component: ContractTemplatesComponent;
  let fixture: ComponentFixture<ContractTemplatesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ContractTemplatesComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ContractTemplatesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
