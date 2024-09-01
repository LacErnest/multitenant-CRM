import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'byteDisplay',
})
export class ByteDisplayPipe implements PipeTransform {
  transform(
    bytes: number,
    displaySize: 'kB' | 'MB' | 'GB' | 'auto' = 'auto'
  ): string {
    let display = displaySize;

    if (displaySize === 'auto') {
      switch (true) {
        case bytes > 1024 * 1024 * 1024:
          display = 'GB';
          break;
        case bytes > 1024 * 1024:
          display = 'MB';
          break;
        case bytes > 1024:
          display = 'kB';
          break;
        default:
          display = null;
          break;
      }
    }

    switch (display) {
      case 'kB':
        return `${(bytes / 1024).toFixed(2)} kB`;
      case 'MB':
        return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
      case 'GB':
        return `${(bytes / 1024 / 1024 / 1024).toFixed(2)} GB`;
      default:
        return `${bytes} bytes`;
    }
  }
}
