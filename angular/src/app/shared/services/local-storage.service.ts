import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class LocalStorageService {
  private observers: { [key: string]: Observable<any> };
  private localStorage;

  constructor() {
    this.localStorage = window.localStorage;
    this.observers = {};
  }

  set(key: string, value: any): void {
    this.localStorage.setItem(key, JSON.stringify(value));
    const event = new StorageEvent('storage', {
      key: key,
      newValue: JSON.stringify(value),
    });
    window.dispatchEvent(event);
  }

  get(key: string): any {
    const value = this.localStorage.getItem(key);
    return value ? JSON.parse(value) : null;
  }

  clear(): void {
    localStorage.clear();
  }

  getObserver(key: string): Observable<any> {
    if (this.observers[key]) {
      return this.observers[key];
    }
    return this.observeKey(key);
  }

  observeKey(key: string): Observable<any> {
    this.observers[key] = new Observable(observer => {
      window.addEventListener(
        'storage',
        event => {
          const key = event.key;
          const value = this.get(key);
          if (this.observers[key]) {
            observer.next(value);
          }
        },
        false
      );
    });
    return this.observers[key];
  }
}
