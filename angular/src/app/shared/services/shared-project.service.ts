import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class SharedProjectService {
  constructor() {}

  project = new Subject<any>();
}
