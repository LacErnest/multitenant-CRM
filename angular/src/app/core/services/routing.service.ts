import { Injectable } from '@angular/core';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root',
})
export class RoutingService {
  private urlTrace: string[] = [];

  public constructor(private router: Router) {}

  public getTraceLength(): number {
    return this.urlTrace.length;
  }

  public getLast(getLastValueWithoutPop = false): string {
    if (getLastValueWithoutPop) {
      return this.urlTrace.length > 0
        ? this.urlTrace[this.urlTrace.length - 1]
        : '';
    }

    return this.urlTrace.length > 0 ? this.urlTrace.pop() : '';
  }

  public setNext(): void {
    this.urlTrace.push(this.router.routerState.snapshot.url);
  }
}
