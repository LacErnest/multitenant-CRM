import { Moment } from 'moment';

export type DecimalFilterValue = [{ min: number; max: number }];
export type IntegerFilterValue = [{ from: number; to: number }];
export type PercentageFilterValue = [{ from: number; to: number }];
export type DateFilterValue = Moment[];
export type UuidFilterValue = { id: string; name: string };
