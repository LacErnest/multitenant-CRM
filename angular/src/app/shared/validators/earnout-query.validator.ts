import moment from 'moment';

export function validateEarnoutQuery(queryParams: any): boolean {
  const currentYear = Number(moment.utc().year());
  const quarter = [1, 2, 3, 4];

  const validQuarter =
    typeof queryParams.quarter !== 'undefined' &&
    quarter.includes(Number(queryParams.quarter)) === true;

  const validYear =
    typeof queryParams.year !== 'undefined' &&
    Number(queryParams.year) >= currentYear - 2 &&
    Number(queryParams.year) <= currentYear;

  return validQuarter === true && validYear === true;
}
