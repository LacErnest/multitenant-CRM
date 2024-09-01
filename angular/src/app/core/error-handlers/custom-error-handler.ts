import { ErrorHandler, Injectable } from '@angular/core';
import { GlobalService } from '../services/global.service';
import { Router } from '@angular/router';
import { SentryErrorHandler } from './sentry/SentryErrorHandler';

@Injectable({
  providedIn: 'root',
})
export class CustomErrorHandler implements ErrorHandler {
  constructor(
    private globalService: GlobalService,
    private router: Router,
    private sentryErrorHandler: SentryErrorHandler
  ) {}

  handleError(error) {
    this.sentryErrorHandler.handleError(error);
    console.error(error);
  }
}
