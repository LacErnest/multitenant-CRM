import {
  DateFilterValue,
  DecimalFilterValue,
  IntegerFilterValue,
  PercentageFilterValue,
  UuidFilterValue,
} from 'src/app/shared/types/filter-value-types';

export interface TablePreferences {
  all_columns?: Column[];
  columns?: Column[];
  filters?: Filter[];
  sorts?: Sort[];
  default_columns?: any[];
  default_filters?: Filter[];
}

export interface Column {
  cast?: string;
  model?: string;
  name: string;
  prop: string;
  type: string;
  format?: string;
  check_on?: boolean;
  children?: string[];
  parent?: string;
  // NOTE: FE fields only
  no_redirect?: boolean;
  uuid_type?: string;
  filter_name?: string;
  hidden?: boolean;
  filterable?: boolean | 'invisible';
}

export interface Filter {
  prop: string;
  type: string;
  value:
    | number[]
    | string[]
    | DecimalFilterValue
    | IntegerFilterValue
    | DateFilterValue
    | UuidFilterValue
    | PercentageFilterValue;
}

export interface Sort {
  dir: 'asc' | 'desc';
  prop: string;
}
