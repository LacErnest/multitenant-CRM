import { Pipe, PipeTransform } from '@angular/core';
import { EnumService } from '../../core/services/enum.service';

@Pipe({
  name: 'enum',
})
export class EnumPipe implements PipeTransform {
  constructor(private enumService: EnumService) {}

  transform(enumName: string, type: 'array' | 'map'): any[] | Map<any, any> {
    switch (type) {
      case 'array':
        return this.enumService.getEnumArray(enumName);
      case 'map':
        return this.enumService.getEnumMap(enumName);
    }
  }
}
