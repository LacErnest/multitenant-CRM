import {
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
  HttpResponse,
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { catchError, tap } from 'rxjs/operators';
import { Observable, throwError } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
import { GlobalService } from '../services/global.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(
    private router: Router,
    private globalService: GlobalService
  ) {}

  intercept(
    req: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    return req.headers.get('X-Auth')
      ? this.externalIntercept(req, next)
      : this.userIntercept(req, next);
  }

  private renewToken(event, url) {
    if (url.indexOf('api/') === 0) {
      if (event.headers.get('X-Authorization') !== null) {
        const token = event.headers
          .get('X-Authorization')
          .replace('Bearer ', '');
        localStorage.setItem('access_token', token);
      } else if (this.globalService.isLoggedIn) {
        localStorage.removeItem('access_token');
        this.globalService.isLoggedIn = false;
        this.router.navigate(['auth/login']);
      }
    }
  }

  private userIntercept(
    req: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    const user = JSON.parse(localStorage.getItem('user'));
    const accessToken = localStorage.getItem('access_token');
    let authReq;
    const url = req.url;
    if (user && accessToken && url.indexOf('api/') === 0) {
      const authHeader = 'Bearer ' + accessToken;
      authReq = req.clone({ setHeaders: { 'X-Authorization': authHeader } });
    } else {
      authReq = req.clone();
    }
    return next
      .handle(authReq)
      .pipe(
        tap((event: HttpEvent<any>) => {
          if (event instanceof HttpResponse) {
            this.renewToken(event, url);
          }
        })
      )
      .pipe(
        catchError(err => {
          switch (err.status) {
            case 401:
              this.globalService.userDetails = undefined;
              this.globalService.isLoggedIn = false;
              this.router
                .navigate(['auth/login'], {
                  queryParams: {
                    returnURL: this.router.routerState.snapshot.url,
                  },
                })
                .then();
              break;
            case 403: {
              this.renewToken(err, url);
              this.router.navigate(['']).then();
              break;
            }
            case 404:
              this.router.navigate(['/404']);
              break;
            case 422:
            case 500:
              this.renewToken(err, url);
              break;
            default:
              if (err instanceof HttpErrorResponse) {
                this.renewToken(err, url);
              }
          }
          return throwError(err.error);
        })
      );
  }

  private externalIntercept(
    req: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    return next.handle(req).pipe(
      catchError(err => {
        switch (err.status) {
          case 401:
            this.globalService.userDetails = undefined;
            this.globalService.isLoggedIn = false;
            this.router.navigate(['']).then();
            break;
          case 403: {
            this.router.navigate(['']).then();
            break;
          }
          case 404:
            this.router.navigate(['/404']);
            break;
          case 422:
          case 500:
            break;
          default:
            break;
        }
        return throwError(err.error);
      })
    );
  }
}
