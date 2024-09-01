import moment from 'moment';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'momentDate' })
export class MomentDatePipe implements PipeTransform {
  constructor() {}

  transform(value: string, format: string): string {
    return moment.utc(value).format(format);
  }
}
