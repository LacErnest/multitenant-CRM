import { Injectable } from '@angular/core';
import { EMPTY, fromEvent, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ResizeService {
  resizeObs: Observable<any>;

  constructor() {
    this.resizeObs =
      typeof window !== 'undefined' ? fromEvent(window, 'resize') : EMPTY;
  }
}
