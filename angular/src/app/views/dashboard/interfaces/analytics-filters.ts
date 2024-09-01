import { FilterType } from 'src/app/views/dashboard/types/analytics-filter-type';

export interface AnalyticsFilters {
  type: FilterType;
  year: number;
  quarter: number;
  month: number;
  week: number;
  day: any; // TODO: add type
}
