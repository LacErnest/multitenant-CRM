import { Pipe, PipeTransform } from '@angular/core';
import { EnumService } from '../../core/services/enum.service';

@Pipe({
  name: 'enumValue',
})
export class EnumValuePipe implements PipeTransform {
  constructor(private enumService: EnumService) {}

  transform(value: any, enumName: string): any {
    return this.enumService.getEnumMap(enumName).get(value);
  }
}
