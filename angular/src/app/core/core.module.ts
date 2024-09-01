import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HeaderComponent } from './layout/header/header.component';
import { RouterModule } from '@angular/router';
import { ClickOutsideModule } from 'ng-click-outside';
import { NgSelectModule } from '@ng-select/ng-select';
import { AppContainerComponent } from './layout/app-container/app-container.component';
import { ToastComponent } from './components/toast/toast.component';
import { SharedModule } from '../shared/shared.module';

@NgModule({
  declarations: [HeaderComponent, AppContainerComponent, ToastComponent],
  imports: [
    CommonModule,
    BrowserAnimationsModule,
    RouterModule,
    ClickOutsideModule,
    NgSelectModule,
    SharedModule,
  ],
  entryComponents: [ToastComponent],
})
export class CoreModule {}
