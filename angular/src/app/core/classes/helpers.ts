import { HttpParams } from '@angular/common/http';
import {
  AbstractControl,
  FormGroup,
  ValidationErrors,
  ValidatorFn,
} from '@angular/forms';
import moment, { Moment } from 'moment';
import { ExportFormat } from '../../shared/enums/export.format';

export class Helpers {
  public static setParam(
    params: HttpParams,
    param: string,
    value: string
  ): HttpParams {
    if (params.has(param)) {
      if (value) {
        params = params.set(param, value);
      } else {
        params = params.delete(param);
      }
    } else if (value) {
      params = params.append(param, value);
    }

    return params;
  }

  public static mapToSelectArray(
    arrayToMap: any[],
    keyPropNames: string[],
    valuePropNames: string[],
    separator?: string
  ): { key: string; value: string }[] {
    return arrayToMap
      ? arrayToMap.map(e => {
          const key = [];
          const value = [];

          for (const k of keyPropNames) {
            key.push(e[k]);
          }

          for (const v of valuePropNames) {
            value.push(e[v]);
          }

          return { key: key.join(separator), value: value.join(separator) };
        })
      : [];
  }

  public static getSVGAvatar = (generator: string, data: string): string => {
    const svg1 = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg1.setAttribute('width', '200');
    svg1.setAttribute('height', '200');

    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    rect.setAttribute('x', '0');
    rect.setAttribute('y', '0');
    rect.setAttribute('width', '200');
    rect.setAttribute('height', '200');
    rect.setAttribute('fill', Helpers.stringToColour(generator));
    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    text.setAttribute('x', '50%');
    text.setAttribute('y', '54%');
    text.setAttribute('dominant-baseline', 'middle');
    text.setAttribute('text-anchor', 'middle');
    text.setAttribute('fill', 'white');
    text.setAttribute('font-size', '60');
    text.setAttribute('font-family', "'Inter var', sans-serif");
    text.setAttribute('font-weight', '600');
    text.textContent = data;
    svg1.appendChild(rect);
    svg1.appendChild(text);
    const svgString = new XMLSerializer().serializeToString(svg1);

    const decoded = unescape(encodeURIComponent(svgString));
    const base64 = btoa(decoded);

    return `data:image/svg+xml;base64,${base64}`;
  };

  public static getExportMIMEType(format: ExportFormat): string {
    switch (format) {
      case ExportFormat.DOCX:
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
      case ExportFormat.XLSX:
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      case ExportFormat.PDF:
        return 'application/pdf';
    }
  }

  public static stringToColour = str => {
    let hash = 0;

    for (let i = 0; i < str.length; i++) {
      hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }

    let colour = '#';

    for (let i = 0; i < 3; i++) {
      const value = (hash >> (i * 8)) & 0xff;
      colour += ('00' + value.toString(16)).substr(-2);
    }

    return colour;
  };

  public static removeEmpty = obj => {
    Object.entries(obj).forEach(([key, val]) => {
      if (val && typeof val === 'object') {
        Object.keys(val).length === 0
          ? delete obj[key]
          : Helpers.removeEmpty(val);
      } else if (val === null) {
        delete obj[key];
      }

      if (
        val === null ||
        (typeof val === 'object' && Object.keys(val).length === 0)
      ) {
        delete obj[key];
      }
    });
  };

  public static getDateRange(
    start: Moment,
    length: 'day' | 'week' | 'month' | 'year' | 'hour' | 'quarter'
  ): string[] {
    return [start.toISOString(), moment(start).endOf(length).toISOString()];
  }

  public static getCurrentDate(): string {
    return moment().toISOString();
  }

  public static quarterOfTheYear(date: Date): number {
    const month = date.getMonth() + 1;
    return Math.ceil(month / 3);
  }

  public static isDate(val: string): boolean {
    return moment(val, moment.ISO_8601, true).isValid();
  }
}

export const atLeastOne =
  (validator: ValidatorFn) =>
  (group: FormGroup): ValidationErrors | null => {
    const hasAtLeastOne =
      group &&
      group.controls &&
      Object.keys(group.controls).some(k => !validator(group.controls[k]));
    return hasAtLeastOne ? null : { atLeastOne: true };
  };

export function ceilNumberToTwoDecimals(n: number): number {
  return Math.round(n * 100) / 100;
}

export function createDownloadLinkAndClick(
  file: Blob,
  filename: string,
  document: Document
): void {
  const link = document.createElement('a');
  document.body.appendChild(link);

  link.setAttribute('href', URL.createObjectURL(file));
  link.setAttribute('download', filename);
  link.setAttribute('id', 'downloadlink');
  link.click();

  document.body.removeChild(link);
}

export function showRequiredError(
  hasRequiredErr: boolean,
  isFieldDirty: boolean
): boolean {
  return hasRequiredErr && isFieldDirty;
}

export function controlHasErrors(control: AbstractControl): boolean {
  return !!control?.errors;
}

export function roundToTwo(num: number): number {
  return +(Math.round(Number(num + 'e+2')) + 'e-2');
}
