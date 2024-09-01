import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { NotificationSettingsFormComponent } from 'src/app/views/legal-entities/modules/notification-settings/containers/notification-settings-form/notification-settings-form.component';

describe('NotificationSettingsFormComponent', () => {
  let component: NotificationSettingsFormComponent;
  let fixture: ComponentFixture<NotificationSettingsFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [NotificationSettingsFormComponent],
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(NotificationSettingsFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
