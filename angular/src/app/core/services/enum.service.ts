import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class EnumService {
  private enums: Map<string, Map<any, any>> = new Map();

  constructor(private http: HttpClient) {}

  hasEnum(enumName): boolean {
    return this.enums.get(enumName) !== undefined;
  }

  getEnumArray(enumName: string): { key; value }[] {
    const arr: { key; value }[] = [];
    if (this.enums.get(enumName)) {
      for (const entry of this.enums.get(enumName)) {
        arr.push({ key: entry[0], value: entry[1] });
      }
    }
    return arr;
  }

  getEnumMap(enumName: string): Map<any, any> {
    return this.enums.get(enumName);
  }

  setEnums(enums: any): void {
    const mappedEnums: any = new Map(Object.entries(enums));
    mappedEnums.forEach(this.updateEnum);
  }

  getEnums(enums: string[]): Observable<any> {
    return this.http.post('api/enum', enums);
  }

  private updateEnum = (value, key, map) => {
    const enumMap = new Map();
    for (const item of Object.entries(value)) {
      enumMap.set(
        !isNaN(item[0] as any) ? parseInt(item[0]) : item[0],
        item[1]
      );
    }
    this.enums.set(key, enumMap);
  };
}
