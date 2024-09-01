import { BrowserModule } from '@angular/platform-browser';
import { ErrorHandler, NgModule } from '@angular/core';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { CoreModule } from './core/core.module';
import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';
import { AuthInterceptor } from './core/interceptors/auth.interceptor';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { ToastrModule } from 'ngx-toastr';
import { ToastComponent } from './core/components/toast/toast.component';
import moment from 'moment';
import {
  OWL_DATE_TIME_LOCALE,
  OWL_MOMENT_DATE_TIME_ADAPTER_OPTIONS,
  OwlDateTimeModule,
  OwlMomentDateTimeModule,
} from '@danielmoncada/angular-datetime-picker';
import * as Sentry from '@sentry/browser';
import { environment } from '../environments/environment';
import { CurrencyMaskInputMode, NgxCurrencyModule } from 'ngx-currency';
import { CustomErrorHandler } from './core/error-handlers/custom-error-handler';

if (environment.sentry_dsn) {
  Sentry.init({
    dsn: environment.sentry_dsn,
    environment: environment.environmentName,
  });
}

export const MOMENT_FORMATS = {
  parseInput: moment.ISO_8601,
  fullPickerInput: moment.ISO_8601,
  datePickerInput: moment.ISO_8601,
  timePickerInput: moment.ISO_8601,
  monthYearLabel: 'MMM YYYY',
  dateA11yLabel: 'LL',
  monthYearA11yLabel: 'MMMM YYYY',
};

const currencyMaskConfig = {
  align: 'left',
  allowNegative: false,
  allowZero: true,
  decimal: '.',
  precision: 2,
  prefix: '',
  suffix: '',
  thousands: ',',
  nullable: true,
  min: 0,
  max: null,
  inputMode: CurrencyMaskInputMode.NATURAL,
};

@NgModule({
  declarations: [AppComponent],
  imports: [
    BrowserModule,
    CoreModule,
    AppRoutingModule,
    HttpClientModule,
    OwlDateTimeModule,
    OwlMomentDateTimeModule,
    BrowserAnimationsModule,
    ToastrModule.forRoot({
      toastComponent: ToastComponent,
      tapToDismiss: false,
      positionClass: 'toast-bottom-right',
    }),
    NgxCurrencyModule.forRoot(currencyMaskConfig),
  ],
  providers: [
    { provide: OWL_DATE_TIME_LOCALE, useValue: 'en-GB' },
    {
      provide: OWL_MOMENT_DATE_TIME_ADAPTER_OPTIONS,
      useValue: { useUtc: true },
    },
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
    {
      provide: ErrorHandler,
      useClass: CustomErrorHandler,
    },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
