import { ErrorHandler, Injectable } from '@angular/core';
import * as Sentry from '@sentry/browser';
import { GlobalService } from '../../services/global.service';

@Injectable({
  providedIn: 'root',
})
export class SentryErrorHandler implements ErrorHandler {
  constructor(private globalService: GlobalService) {}

  handleError(error) {
    const user = this.globalService.userDetails;
    if (user) {
      Sentry.withScope(scope => {
        scope.setUser({ email: user.email, id: user.id });
        Sentry.captureException(error.originalError || error);
      });
    } else {
      Sentry.captureException(error.originalError || error);
    }
  }
}
